<?php

namespace Fuga\CommonBundle\Manager;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;

class UserManager extends ModelManager {
	
	protected $entityTable = 'user';
	protected $user;
	protected $hash;
	protected $loginTries = 3;
	protected $lockPeriod = 900; //seconds
	protected $currentUser;


	public function isAuthenticated() {
		return $this->check();
	}

	public function isSecuredArea()
	{
		if (preg_match('/^'.(PRJ_REF ? '\\'.PRJ_REF : '').'\/admin\/(logout|forget|password)/', $_SERVER['REQUEST_URI'])) {
			return false;
		}

		return preg_match('/^'.(PRJ_REF ? '\\'.PRJ_REF : '').'\/admin/', $_SERVER['REQUEST_URI']);
	}

	public function isClosedArea()
	{
		return 'Y' == PROJECT_LOCKED && !preg_match('/^'.(PRJ_REF ? '\\'.PRJ_REF : '').'\/admin/', $_SERVER['REQUEST_URI']);
	}

	public function getCurrentUser()
	{
		if (!$this->user) {
			if ($this->isDeveloper()) {
				$this->user = array(
					'id' => 0,
					'name' => 'Developer',
					'lastname' => 'D',
					'email'    => DEV_EMAIL,
					'group_id' => 1,
					'group_id_title' => 'Администратор',
					'group_id_name' => 'admin',
					'is_admin' => 1,
					'is_active' => 1,
				);
			} else {
				$login = $this->get('session')->get('fuga_user');
				$sql = "
					SELECT u.*, g.name as group_id_name, g.title as group_id_title FROM user u
					JOIN user_group g ON u.group_id=g.id
					WHERE u.login = :login OR u.email = :login LIMIT 1";
				$stmt = $this->get('connection')->prepare($sql);
				$stmt->bindValue('login', $login);
				$stmt->execute();
				$this->user = $stmt->fetch();
				if ($this->user) {
					$sql = 'SELECT module_id, group_id FROM user_group_module WHERE group_id= :id';
					$stmt = $this->get('connection')->prepare($sql);
					$stmt->bindValue('id', $this->user['group_id']);
					$stmt->execute();
					$modules = $stmt->fetchAll();
					$rules = array();
					foreach($modules as $module) {
						$rules[] = $module['module_id'];
					}
					if (!$rules && $this->isSecuredArea()) {
						$response = new RedirectResponse('/', 302);

						$response->send();
					}
					$this->user['rules'] = $rules;
				}
			}
		}

		return $this->user;
	}

	public function getCurrentUserPublic()
	{
		if (!$this->currentUser){
			$this->currentUser = $this->getCurrentUser();
			if ($this->currentUser) {
				unset($this->currentUser['password']);
				unset($this->currentUser['hash']);
				unset($this->currentUser['token']);
			}
		}

		return $this->currentUser;
	}

	private function check()
	{
		$login = $this->get('session')->get('fuga_user');
		$token = $this->get('session')->get('fuga_key');

		if (!$login && !$token) {
			$login = $this->get('request')->cookies->get('fuga_user');
			$token = $this->get('request')->cookies->get('fuga_key');
		}
		if ($login && $token) {
			if ($token == $this->token(DEV_USER, DEV_PASS)) {
				$user = array('login' => DEV_USER);
			} else {
				$user = $this->getTable('user')->getItem("token='".$token."'");
			}

			if ($user) {
				$this->hash = $token;
				$this->get('session')->set('fuga_user', $login);
				$this->get('session')->set('fuga_key', $token);

				return true;
			}
		}

		return false;
	}

	public function unlock()
	{
		$this->get('session')->remove('bruteforce');

		return true;
	}

	public function lock()
	{
		$times = 1;

		if ($this->get('session')->has('bruteforce')) {
			$times = $this->get('session')->get('bruteforce');
			$times++;
			if ($times >= $this->loginTries) {
				$this->get('session')->remove('bruteforce');

				$this->get('connection')->insert(
					'user_secure',
					[
						'ip_addr' => ip2long($_SERVER['REMOTE_ADDR']),
						'unlocktime' => time() + $this->lockPeriod,
						'created' => new \DateTime()
					],
					[
						\PDO::PARAM_INT,
						\PDO::PARAM_INT,
						'datetime'
					]
				);

				return true;
			}
		}

		$this->get('session')->set('bruteforce', $times);

		return false;
	}

	public function isLocked()
	{
		$sql = 'SELECT * FROM user_secure WHERE ip_addr = :ip_addr AND unlocktime > :ctime LIMIT 1';
		$stmt = $this->get('connection')->prepare($sql);
		$stmt->bindValue('ip_addr', ip2long($_SERVER['REMOTE_ADDR']));
		$stmt->bindValue('ctime', time());
		$stmt->execute();
		$ip = $stmt->fetch();

		return $ip ? true : false;
	}

	public function logout()
	{
		$this->get('session')->invalidate();
	}

	public function login($login, $password, $isRemember = false)
	{
		$passwordHash = hash('sha512', $password);
		if ($login == DEV_USER && $passwordHash == DEV_PASS) {
			$user = array('login' => $login, 'id' => 0);
		} else {
			$sql = "SELECT id, login FROM user WHERE login= :login AND password= :password AND is_active=1 AND group_id<>0 LIMIT 1";
			$stmt = $this->get('connection')->prepare($sql);
			$stmt->bindValue("login", $login);
			$stmt->bindValue("password", $passwordHash);
			$stmt->execute();
			$user = $stmt->fetch();
		}
		if ($user){
			$this->unlock();
			$token = $this->token($login, $passwordHash);
			$response = new RedirectResponse($this->get('request')->getRequestUri());
			$this->get('session')->set('fuga_user', $user['login']);
			$this->get('session')->set('fuga_key', $token);
			$this->getTable('user')->update(
				array('token' => $token),
				array('id' => $user['id'])
			);

			if ($isRemember) {
				$this->getTable('user')->update(
					array('token' => $token),
					array('id' => $user['id'])
				);
				$response->headers->setCookie(new Cookie('fuga_user', $user['login'], time()+AUTH_TTL));
				$response->headers->setCookie(new Cookie('fuga_key', $token, time()+AUTH_TTL));
			}

			return $response;
		}

		$this->lock();

		return false;
	}

	private function token($login, $password)
	{
		return hash('sha512', $password.$login.$this->get('request')->getClientIp());
	}

	public function getGroup($name)
	{
		return $this->getTable('user_group')->getItem('user_group', "name='$name'");
	}

	public function isGroup($name)
	{
		$group = $this->getGroup($name);
		$user = $this->getCurrentUser();

		return !empty($user['group_id']) && !empty($group['id']) && $user['group_id'] == $group['id'];
	}

	public function isAdmin()
	{
		return $this->get('session')->get('fuga_user') == 'admin';
	}

	public function isDeveloper()
	{
		return $this->get('session')->get('fuga_user') == 'dev';
	}

	public function isSuperuser()
	{
		return $this->isAdmin() || $this->isDeveloper();
	}

	public function isLocal()
	{
		return $this->get('request')->getClientIp() == gethostbyname($this->get('request')->server->get('SERVER_NAME'));
	}

	public function isServer()
	{
		return isset($_SERVER['HTTP_REFERER']) && strstr($_SERVER['HTTP_REFERER'], $_SERVER['SERVER_NAME']);
	}

}