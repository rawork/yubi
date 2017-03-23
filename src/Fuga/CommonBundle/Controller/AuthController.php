<?php

namespace Fuga\CommonBundle\Controller;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AuthController extends Controller
{
	public function login()
	{
		$message = null;
		if ('POST' == $_SERVER['REQUEST_METHOD']) {
			if ($this->get('security')->isLocked()) {
				return $this->reload();
			}
			$login = $this->get('request')->request->get('_user');
			$password = $this->get('request')->request->get('_password');
			$is_remember = $this->get('request')->request->get('_remember_me');
			
			if (!$login || !$password){
				$this->get('session')->set('danger', 'Неверный Логин или Пароль');
				$this->get('security')->lock();
			} elseif ($this->get('security')->isServer()) {
				$res = $this->get('security')->login($login, $password, $is_remember);
				if ($res) {
					return $res;
				} else {
					$this->get('session')->set('danger', 'Неверный Логин или Пароль');
					$this->get('security')->lock();
				}
			}
			
			return $this->reload();
		}

		$message = $this->flash('danger');

		$locked = $this->get('security')->isLocked();

		return new Response($this->render('admin/form/login', compact('message', 'locked')));
	}
	
	public function forget()
	{
		$message = null;
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$login  = $this->get('request')->request->get('_user');
			$sql = 'SELECT id, login, email FROM user_user WHERE login= :login OR email = :login ';
			$stmt = $this->get('connection')->prepare($sql);
			$stmt->bindValue('login', $login);
			$stmt->execute();
			$user = $stmt->fetch();
			if ($user) {
				$key = $this->get('util')->genKey(32);
				$this->get('connection')->update(
						'user_user', 
						array('hashkey' => $key), 
						array('id' => $user['id'])
				);
				$text = 'Информационное сообщение сайта '.$_SERVER['SERVER_NAME']."\n";
				$text .= '------------------------------------------'."\n";
				$text .= 'Вы запросили ваши регистрационные данные.'."\n\n";
				$text .= 'Ваша регистрационная информация:'."\n";
				$text .= 'ID пользователя: '.$user['id']."\n";
				$text .= 'Логин: '.$user['login']."\n\n";
				$text .= 'Для смены пароля перейдите по следующей ссылке:'."\n";
				$text .= 'http://'.$_SERVER['SERVER_NAME'].PRJ_REF.'/admin/password/'.$key."\n\n";
				$text .= 'Сообщение сгенерировано автоматически.'."\n";
				$this->get('mailer')->send(
					'Новые регистрационные данные. Сайт '.$_SERVER['SERVER_NAME'],
					nl2br($text),
					$user['email']
				);
				$this->get('session')->set('success', 'Новые параметры авторизации отправлены Вам на Электронную почту!');
				return $this->reload();
			} else {
				$this->get('session')->set('danger', 'Пользователь не найден');
				return $this->reload();
			}
		}
		$message = $this->flash('danger') ?: $this->flash('success');

		return new Response($this->render('admin/form/forget', compact('message')));
	}
	
	public function logout()
	{
		$login = $this->get('session')->get('fuga_user');

		if (!$login) {
			return $this->redirect($this->generateUrl('admin_index'));
		}

		$this->get('security')->logout();

		if (empty($_SERVER['HTTP_REFERER']) || preg_match('/^'.(PRJ_REF ? '\\'.PRJ_REF : '').'\/admin\/logout/', $_SERVER['HTTP_REFERER'])) {
			$uri = $this->generateUrl('admin_index');
		} else {
			$uri = $_SERVER['HTTP_REFERER'];
		}

		$response = new RedirectResponse($uri);
		$response->headers->clearCookie('fuga_key');
		$response->headers->clearCookie('fuga_user');

		return $response;
	}
	
	public function password($key)
	{
		$user = $this->getTable('user_user')->getItem("hashkey='".$key."'");

		if ($user && !empty($user['email'])) {
			$password = $this->get('util')->genKey();

			$this->getTable('user_user')->update(
					array('hashkey' => '', 'password' => hash('sha512', $password)),
					array('id' => $user['id'])
			);

			$text = 'Информационное сообщение сайта '.$_SERVER['SERVER_NAME']."\n";
			$text .= '------------------------------------------'."\n";
			$text .= 'Вы запросили ваши регистрационные данные.'."\n";
			$text .= 'Ваша регистрационная информация:'."\n";
			$text .= 'ID пользователя: '.$user['id']."\n";
			$text .= 'Логин: '.$user['login']."\n";
			$text .= 'Пароль: '.$password."\n\n";
			$text .= 'Сообщение сгенерировано автоматически.'."\n";
			$this->get('mailer')->send(
				'Новые регистрационные данные. Сайт '.$_SERVER['SERVER_NAME'],
				nl2br($text),
				$user['email']
			);
		}

		return $this->redirect($this->generateUrl('admin_index'));
	}

	public function closed()
	{
		$response = new Response();
		$response->setContent($this->render('page.closed', array('project_logo' => PRJ_LOGO)));

		return $response;
	}
	
}
