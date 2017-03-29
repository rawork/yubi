<?php

namespace Fuga\AdminBundle\Manager;

use Fuga\CommonBundle\Manager\ModelManager;
use Symfony\Component\Yaml\Yaml;

class MenuManager extends ModelManager
{
	protected $config;

	protected function getConfig()
	{
		if (!$this->config) {
			$this->config = Yaml::parse(file_get_contents(PRJ_DIR.'/app/config/admin.menu.yml'));
		}

		return $this->config;
	}

	public function getEntitiesByModule($moduleName)
	{
		$ret = array();
		$module = $this->container->getManager('Fuga:Common:Module')->getByName($moduleName);
		$tables = $this->container->getManager('Fuga:Common:Table')->getByModuleName($moduleName);

		foreach ($tables as $table) {
			if (empty($table->params['is_hidden'])) {
				$ret[] = array (
					'ref' => $this->get('router')->getGenerator()->generate(
						'admin_entity_index',
						array('state' => $module['state'], 'module' => $module['name'], 'entity' => $table->getName())
					),
					'name' => $table->title
				);
			}
		}
		if ($this->get('security')->isSuperuser()) {
			if ($this->container->getManager('Fuga:Common:Param')->findAll($module['name'])) {
				$ret[] = array (
					'ref' => $this->get('router')->getGenerator()->generate(
						'admin_module_setting',
						array('state' => $module['state'], 'module' => $module['name'])
					),
					'name' => 'Настройки'
				);
			}
		}
		if ($module['name'] == 'config' && $this->get('security')->isSuperuser()) {
			$ret[] = array (
				'ref' => $this->get('router')->getGenerator()->generate('admin_service'),
				'name' => 'Обслуживание'
			);
		}

		$config = $this->getConfig();

		foreach ($config as $var => $data) {
			if ($module['name'] == $var) {
				$ret[] = array (
					'ref' => $this->get('router')->getGenerator()->generate($data['route']),
					'name' => $data['title']
				);
			}
		}

		return $ret;
	}

	// TODO доработать проверку прав на модуль, не используется
	function isAvailable()
	{
		return $this->get('security')->isSuperuser() || 1 == $this->users[$this->get('session')->get('fuga_user')];
	}

	public function getModulesByState($state, $currentModule = '')
	{
		$modules = array();
		$modules0 = $this->container->getManager('Fuga:Common:Module')->getByState($state);
		if ($modules0) {
			foreach ($modules0 as $module) {
				$modules[] = array(
					'name' => $module['name'],
					'title' => $module['title'],
					'current' => $module['name'] == $currentModule
				);
			}
		}

		return $modules;
	}
}