<?php

namespace Fuga\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MenuController extends AdminController {

	public function state($state, $module = '')
	{
		if (!$this->get('session')->get('fuga_user')) {
			if ($this->get('request')->isXmlHttpRequest()) {
				return json_encode(['error' => true]);
			}
		}

		$modules = [];
		$modules0 = $this->getManager('Fuga:Admin:Menu')->getModulesByState($state);

		if ($module) {
			$entities = $this->getManager('Fuga:Admin:Menu')->getEntitiesByModule($module);
		}

		foreach ($modules0 as $mod) {
			$modules[] = [
				'name' => $mod['name'],
				'title' => $mod['title'],
				'submenu' => $mod['name'] == $module ? $this->render('@Admin/menu/module', compact('entities')) : '',
			];
		}

		$text = $this->get('templating')->render('@Admin/menu/state', compact('state', 'modules', 'module'));

		if ($this->get('request')->isXmlHttpRequest()) {
			$response = new JsonResponse();
			$response->setData(['content' => $text]);

			return $response;
		} else {
			return $text;
		}
	}

	public function module($module) {
		if (!$this->get('session')->get('fuga_user')) {
			if ($this->get('request')->isXmlHttpRequest()) {
				return json_encode(['error' => true]);
			}
		}

		$entities = $this->getManager('Fuga:Admin:Menu')->getEntitiesByModule($module);

		$text = $this->render('@Admin/menu/module', compact('entities'));

		if ($this->get('request')->isXmlHttpRequest()) {
			$response = new JsonResponse();
			$response->setData(['content' => $text]);

			return $response;
		}

		return $text;
	}

	public function entity($links)
	{
		return $this->render('@Admin/menu/entity', compact('links'));
	}
} 