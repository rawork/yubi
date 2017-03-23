<?php

namespace Fuga\CommonBundle\Model;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Fuga\Component\DB\Table;

class TableManager extends ModelManager
{
	protected $tables = [];
	protected $models = [];

//	private function getAllTables()
//	{
//		// TODO кешировать инициализацию всех таблиц
//		$ret = array();
//		$this->modules = $this->tempmodules;
//		$sql = "SELECT id, sort, name, title, 'content' AS ctype FROM config_module ORDER BY sort, title";
//		$stmt = $this->get('connection')->prepare($sql);
//		$stmt->execute();
//		while ($module = $stmt->fetch()) {
//			$this->modules[$module['name']] = array(
//				'id'    => $module['id'],
//				'name'  => $module['name'],
//				'title' => $module['title'],
//				'ctype' => $module['ctype'],
//				'entities' => array()
//			);
//		}
//		foreach ($this->modules as $module) {
//			$className = 'Fuga\\CommonBundle\\Model\\'.ucfirst($module['name']);
//
//			if (class_exists($className)) {
//				$model = new $className();
//				foreach ($model->tables as $table) {
//					$table['is_system'] = true;
//					$ret[$table['module'].'_'.$table['name']] = new DB\Table($table, $this);
//				}
//			}
//		}
//		$sql = "SELECT t.*, m.name as module
//				FROM table_table t
//				JOIN config_module m ON t.module_id=m.id
//				WHERE t.publish=1 ORDER BY t.sort";
//		$stmt = $this->get('connection')->prepare($sql);
//		$stmt->execute();
//		$tables = $stmt->fetchAll();
//		foreach ($tables as $table) {
//			$ret[$table['module'].'_'.$table['name']] = new DB\Table($table, $this);
//		}
//
//		return $ret;
//	}

	public function getByName($name)
	{
		// todo init table cache for prod
		// todo init models cache for prod

		if (!isset($this->tables[$name])) {

			list($module, $table) = explode('_', $name);

			if (!$module || !$table) {
				throw new \Exception('Некорректное имя таблицы "'.$name.'"');
			}

			$table = preg_replace('/^'.$module.'_/', '', $name);

			if (!isset($this->models[$module])) {
				$className = 'Fuga\\CommonBundle\\Model\\' . ucfirst($module);

				if (class_exists($className)) {
					$this->models[$module] = new $className();
				} else {
					$this->models[$module] = false;
				}
			}

			if (is_object($this->models[$module])) {
				$model = $this->models[$module];

				if (isset($model->tables[$table])) {
					$tableData = $model->tables[$table];
					$tableData['is_system'] = true;
					$this->tables[$module . '_' . $table] = new Table($tableData, $this->get('container'));
				}
			}

			$sql = "SELECT t.*, m.name as module
				FROM table_table t
				JOIN config_module m ON t.module_id=m.id
				WHERE m.name = :module AND t.name = :table AND t.publish=1 ORDER BY t.sort LIMIT 1";
			$stmt = $this->get('connection')->prepare($sql);
			$stmt->bindValue('module', $module);
			$stmt->bindValue('table', $table);
			$stmt->execute();
			$tableData = $stmt->fetch();
			if ($tableData) {
				$this->tables[$module.'_'.$table] = new Table($tableData, $this->get('container'));
			}
		}

		if (!isset($this->tables[$name])) {
			throw new \Exception('Таблица "' . $name . '" не существует');
		}

		return $this->tables[$name];
	}

	public function getByModuleName($name)
	{
		$tables = array();
		foreach ($this->tables as $table) {
			if ($table->moduleName == $name)
				$tables[$table->tableName] = $table;
		}
		return $tables;
	}

	public function setLocale($site)
	{
		if (!$this->get('session')->get('locale')) {
			$this->get('session')->set('locale', PRJ_LOCALE);
		}
		if ($_SERVER['REQUEST_METHOD'] == 'POST' && $this->get('request')->request->get('locale')) {
			$this->get('session')->set('locale', $this->get('request')->request->get('locale'));
			$response = new RedirectResponse($_SERVER['REQUEST_URI'], 302);
			$response->send();
			exit;
		} elseif (substr($site['url'], 0, 6) != '/admin')  {
			$this->get('session')->set('locale', $site['language']);
		}
	}
} 