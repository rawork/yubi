<?php

namespace Fuga\CommonBundle\Model;


use Fuga\CommonBundle\Model\ModelManager;
use Symfony\Component\Yaml\Yaml;

class ModuleManager extends ModelManager
{
	protected $config;
	protected $initialized = false;
	protected $states = [
		'content' => 'Структура и контент',
		'service' => 'Сервисы',
		'system'  => 'Настройки',
	];
	protected $coreModules = [
		'user' => [
			'name'  => 'user',
			'title' => 'Пользователи',
			'ctype' => 'system',
			'entitites' => []
		],
		'template' => [
			'name'  => 'template',
			'title' => 'Шаблоны',
			'ctype' => 'system',
			'entitites' => []
		],
		'config' => [
			'name'	=> 'config',
			'title' => 'Настройки',
			'ctype' => 'system',
			'entitites' => []
		],
		'table' => [
			'name'	=> 'table',
			'title' => 'Таблицы',
			'ctype' => 'system',
			'entitites' => []
		],
		'subscribe' => [
			'name'  => 'subscribe',
			'title' => 'Подписка',
			'ctype' => 'service',
			'entitites' => []
		],
		'form' => [
			'name'  => 'form',
			'title' => 'Формы',
			'ctype' => 'service',
			'entitites' => []
		],
	];
	protected $modules = [];
	protected $personalModules = [];

	public function getAll()
	{
		if (!$this->initialized) {
			$this->modules = $this->coreModules;
			$sql = "SELECT id, sort, name, title, 'content' AS ctype FROM config_module ORDER BY sort, title";
			$stmt = $this->get('connection')->prepare($sql);
			$stmt->execute();

			while ($module = $stmt->fetch()) {
				$this->modules[$module['name']] = array(
					'id' => $module['id'],
					'name' => $module['name'],
					'title' => $module['title'],
					'ctype' => $module['ctype'],
					'entities' => array()
				);
			}

			$this->initialized;
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
			if ($state == $module['ctype']) {
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
