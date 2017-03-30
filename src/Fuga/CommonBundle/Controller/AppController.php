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

		$this->container->register('session', $session);
		$this->container->register('request', $request);

		$site = $this->getManager('Fuga:Common:Site')->detectSite($_SERVER['REQUEST_URI']);
		$this->getManager('Fuga:Common:Locale')->setLocale($site);

		$this->getManager('Fuga:Common:Template')->setVar('mainurl', $site['url']);

		if ($this->get('security')->isSecuredArea() && !$this->get('security')->isAuthenticated()) {
			return $this->call('Fuga:Common:Auth:login');
		}

		if ($this->get('security')->isClosedArea()) {
			return $this->call('Fuga:Common:Auth:closed');
		}

		try {
			$parameters = $this->get('router')->match(array_shift(explode('?', $site['url'])));

			return $this->container->callAction($parameters['_controller'], $parameters);
		} catch(ResourceNotFoundException $e) {
			throw new NotFoundHttpException('Несуществующая страница');
		}
	}
}
