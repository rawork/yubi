<?php

namespace Fuga\CommonBundle\Controller;

use Fuga\AdminBundle\AdminInterface;
use Fuga\Component\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class AppController extends Controller
{
	public function handle()
	{
		$request = Request::createFromGlobals();

		$session = new Session();
		$session->start();

		$this->get('container')->register('session', $session);
		$this->get('container')->register('request', $request);

		$site = $this->getManager('Fuga:Common:Site')->detectSite($_SERVER['REQUEST_URI']);
		$this->getManager('Fuga:Common:Locale')->setLocale($site);

		$this->get('container')->setVar('mainurl', $site['url']);

		if ($this->get('security')->isSecuredArea() && !$this->get('security')->isAuthenticated()) {
			$controller = new SecurityController();

			return $controller->login();
		}

		if ($this->get('security')->isClosedArea()) {
			$controller = new SecurityController();

			return $controller->closed();
		}

		try {
			$parameters = $this->get('routing')->match(array_shift(explode('?', $site['url'])));

			return $this->get('container')->callAction($parameters['_controller'], $parameters);
		} catch(ResourceNotFoundException $e) {
			throw new NotFoundHttpException('Несуществующая страница');
		}
	}
}
