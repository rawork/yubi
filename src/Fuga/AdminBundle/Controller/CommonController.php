<?php

namespace Fuga\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CommonController extends AdminController
{
	public function state($state)
	{
		$modules = $this->getManager('Fuga:Admin:Menu')->getModulesByState($state);
		$response = new Response();
		$response->setContent($this->render('admin/index', compact('modules', 'state')));
		$response->prepare($this->get('request'));

		return $response;
	}

	public function module($state, $module)
	{
		$entities = $this->getManager('Fuga:Admin:Menu')->getEntitiesByModule($module);
		$response = new Response();
		$response->setContent($this->render('admin/module', compact('entities', 'state', 'module')));
		$response->prepare($this->get('request'));

		return $response;
	}

	public function setting($state, $module)
	{
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$params = $this->getManager('Fuga:Common:Param')->findAll($module);
			foreach ($params as $param) {
				if ($rawValue = $this->get('request')->request->get('param_'.$param['name'])){

					if ($param['type'] == 'date') {
						$hour = $this->get('request')->request->get('param_'.$param['name'].'_hour');
						$minute = $this->get('request')->request->get('param_'.$param['name'].'_minute');
						$rawValue .= ' '.$hour.':'.$minute;
					}

					if ($value = $this->getManager('Fuga:Common:Param')->validate($rawValue, $param)) {
						$this->get('connection')->update('config_param',
							array('value' => $value),
							array('name' => $param['name'], 'module' => $param['module'])
						);
					}
				} elseif ($param['type'] == 'boolean') {
					$this->get('connection')->update('config_param',
						array('value' => 0),
						array('name' => $param['name'], 'module' => $param['module'])
					);
				}
			}
			$this->get('session')->getFlashBag()->add('admin.message', 'Настройки сохранены');

			return $this->redirect($this->generateUrl(
				'admin_module_setting',
				array('state' => $state, 'module' => $module)
			));
		}

		$title = 'Настройки модуля';
		$message = null;
		if ($adminMessage = $this->get('session')->getFlashBag()->get('admin.message')) {
			$message = array_shift($adminMessage);
		}

		$params = $this->getManager('Fuga:Common:Param')->findAll($module);

		$response = new Response();
		$response->setContent(
			$this->render('admin/common/setting',
			compact('state', 'module', 'title', 'message', 'params'))
		);
		$response->prepare($this->get('request'));

		return $response;
	}
} 