<?php

namespace Fuga\AdminBundle\Model;

use Fuga\CommonBundle\Model\ModelManager;
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
		$module = $this->get('container')->getManager('Fuga:Common:Module')->getByName($moduleName);
		$tables = $this->get('container')->getManager('Fuga:Common:Table')->getByModuleName($moduleName);

		foreach ($tables as $table) {
			if (empty($table->params['is_hidden'])) {
				$ret[] = array (
					'ref' => $this->get('routing')->getGenerator()->generate(
						'admin_entity_index',
						array('state' => $module['ctype'], 'module' => $module['name'], 'entity' => $table->name)
					),
					'name' => $table->title
				);
			}
		}
		if ($this->get('security')->isSuperuser()) {
			if ($this->get('container')->getManager('Fuga:Common:Param')->findAll($module['name'])) {
				$ret[] = array (
					'ref' => $this->get('routing')->getGenerator()->generate(
						'admin_module_setting',
						array('state' => $module['ctype'], 'module' => $module['name'])
					),
					'name' => 'Настройки'
				);
			}
		}
		if ($module['name'] == 'config' && $this->get('security')->isSuperuser()) {
			$ret[] = array (
				'ref' => $this->get('routing')->getGenerator()->generate('admin_service'),
				'name' => 'Обслуживание'
			);
		}

		$config = $this->getConfig();

		foreach ($config as $var => $data) {
			if ($module['name'] == $var) {
				$ret[] = array (
					'ref' => $this->get('routing')->getGenerator()->generate($data['route']),
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
		$modules0 = $this->get('container')->getManager('Fuga:Common:Module')->getByState($state);
		if ($modules0) {
			$basePath = PRJ_REF.'/bundles/admin/img/module/';
			foreach ($modules0 as $module) {
				$icon = $this->get('fs')->exists(PRJ_DIR.$basePath.$module['name'].'.gif')
					? $basePath.$module['name'].'.gif'
					: $basePath.'folder'.'.gif';
				$modules[] = array(
					'name' => $module['name'],
					'title' => $module['title'],
					'icon' => $icon,
					'current' => $module['name'] == $currentModule
				);
			}
		}

		return $modules;
	}
}