<?php

namespace Fuga\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MenuController extends AdminController {

	public function state($state, $module = '')
	{
		if (!$this->get('session')->get('fuga_user')) {
			if ($this->get('request')->isXmlHttpRequest()) {
				return json_encode(array('error' => true));
			}
		}

		$modules = array();
		$modules0 = $this->getManager('Fuga:Admin:Menu')->getModulesByState($state);

		if ($module) {
			$entities = $this->getManager('Fuga:Admin:Menu')->getEntitiesByModule($module);
		}

		foreach ($modules0 as $mod) {
			$modules[] = array(
				'name' => $mod['name'],
				'title' => $mod['title'],
				'submenu' => $mod['name'] == $module ? $this->render('admin/menu/module', compact('entities')) : '',
			);
		}

		$text = $this->get('templating')->render('admin/menu/state', compact('state', 'modules', 'module'));

		if ($this->get('request')->isXmlHttpRequest()) {
			$response = new JsonResponse();
			$response->setData(array('content' => $text));

			return $response;
		} else {
			return $text;
		}
	}

	public function module($module) {
		if (!$this->get('session')->get('fuga_user')) {
			if ($this->get('request')->isXmlHttpRequest()) {
				return json_encode(array('error' => true));
			}
		}

		$entities = $this->getManager('Fuga:Admin:Menu')->getEntitiesByModule($module);

		$text = $this->render('admin/menu/module', compact('entities'));
		if ($this->get('request')->isXmlHttpRequest()) {
			$response = new JsonResponse();
			$response->setData(array('content' => $text));

			return $response;
		} else {
			return $text;
		}
	}

	public function entity($links)
	{
		return $this->render('admin/menu/entity', compact('links'));
	}
} 