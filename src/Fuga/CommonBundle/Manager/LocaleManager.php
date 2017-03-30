<?php

namespace Fuga\CommonBundle\Manager;

use Symfony\Component\HttpFoundation\RedirectResponse;

class LocaleManager extends ModelManager
{
	public function getLocales()
	{
		return array('ru');
	}

	public function getCurrentLocale()
	{
		return $this->container->get('session')->get('locale');
	}

	public function setLocale($site)
	{
		if (!$this->container->get('session')->get('locale')) {
			$this->container->get('session')->set('locale', PRJ_LOCALE);
		}
		if ($_SERVER['REQUEST_METHOD'] == 'POST' && $this->container->get('request')->request->get('locale')) {
			$this->container->get('session')->set('locale', $this->container->get('request')->request->get('locale'));
			$response = new RedirectResponse($_SERVER['REQUEST_URI'], 302);
			$response->send();
			exit;
		} elseif (substr($site['url'], 0, 6) != '/admin')  {
			$this->container->get('session')->set('locale', $site['language']);
		}
	}
} 