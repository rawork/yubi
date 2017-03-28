<?php

namespace Fuga\CommonBundle\Model;


use Symfony\Component\Yaml\Yaml;

class ModuleManager extends ModelManager
{
	protected $states = [
		'content' => 'Структура и контент',
		'service' => 'Сервисы',
		'system'  => 'Настройки',
	];
	protected $modules = [];
	protected $personalModules = [];
	protected $config;

	protected function getConfig()
	{
		if (!$this->config) {
			$this->config = Yaml::parse(file_get_contents(PRJ_DIR.'/app/config/modules.yml'));
		}

		return $this->config;
	}

	public function getAll()
	{
		if (empty($this->modules)) {
			$config = $this->getConfig();

			foreach ($config as $name => $module) {
				$module['entities'] = [];
				$this->modules[$name] = $module;
			}
		}

		return $this->modules;
	}

	public function getPersonal()
	{
		if (empty($this->personalModules)) {
			if ($this->get('security')->isSuperuser()) {
				$this->personalModules = $this->modules;
			} elseif ($user = $this->get('security')->getCurrentUser()) {
				if (empty($user['rules'])) {
					$user['rules'] = array();
				}
				$this->personalModules = $this->coreModules;
				if (!$user['is_admin']) {
					unset($this->personalModules['config'], $this->personalModules['user'], $this->personalModules['template'], $this->personalModules['table']);
				}
				$sql = 'SELECT id, sort, name, title, \'content\' AS ctype 
					FROM config_module WHERE id IN ('.implode(',', $user['rules']).') ORDER BY sort, title';
				$stmt = $this->get('connection')->prepare($sql);
				$stmt->execute();
				$this->personalModules = array_merge($this->personalModules, $stmt->fetchAll());
			}
		}

		return $this->personalModules;
	}

	public function getByState($state)
	{
		$modules = array();
		foreach ($this->getPersonal() as $module) {
			if ($state == $module['state']) {
				$modules[$module['name']] = $module;
			}
		}

		return $modules;
	}

	public function getByName($name)
	{
		if (empty($this->modules[$name])) {
			throw new \Exception('Модуль "'.$name.'" не найден');
		}

		return $this->modules[$name];
	}

	public function getStates()
	{
		return $this->states;
	}

}
