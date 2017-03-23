<?php

namespace Fuga\CommonBundle\Model;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Fuga\Component\DB\Table;

class TableManager extends ModelManager
{
	protected $tables = [];
	protected $tableData = [];
	protected $models = [];

	public function getAll($modules)
	{
		if (empty($this->tables)) {

			foreach ($modules as $module) {
				$className = 'Fuga\\CommonBundle\\Model\\'.ucfirst($module['name']);
				if (class_exists($className)) {
					$model = new $className();

					foreach ($model->tables as $table) {
						$table['is_system'] = true;
						$this->tables[$table['module'].'_'.$table['name']] = new Table($table, $this->get('container'));
					}
				}
			}
			$sql = "SELECT t.*, m.name as module
				FROM table_table t
				JOIN config_module m ON t.module_id=m.id
				WHERE t.publish=1 ORDER BY t.sort";
			$stmt = $this->get('connection')->prepare($sql);
			$stmt->execute();
			while ($table = $stmt->fetch()) {
				$this->tableData[$table['module'].'_'.$table['name']] = $table;
				$this->tables[$table['module'].'_'.$table['name']] = new Table($table, $this->get('container'));
			}
		}

		return $this->tables;
	}


	public function getByName($name)
	{
		// todo init table cache for prod
		// todo init models cache for prod

		if (!isset($this->tables[$name]) && isset($this->tableData[$name])) {
			$this->tables[$name] = new Table($this->tableData[$name], $this->get('container'));
		} elseif (!isset($this->tables[$name])) {

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
				WHERE m.name = :module AND t.name = :table AND t.publish=1 ORDER BY t.sort";
			$stmt = $this->get('connection')->prepare($sql);
			$stmt->bindValue('module', $module);
			$stmt->bindValue('table', $table);
			$stmt->execute();

			$tableData = $stmt->fetch();
			if ($tableData){
				$tableData['module'] = $module;
				$this->tableData[$name] = $tableData;
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
		$tables = [];
		foreach ($this->tables as $table) {
			if ($table->moduleName == $name) {
				$tables[$table->tableName] = $table;
			}
		}

		return $tables;
	}

	public function deleteItem($table, $query)
	{
		$ids = $this->deleteRelations($table, $this->getByName($table)->getItems(!empty($query) ? $query : '1<>1'));
		if ($ids) {
			return $this->getByName($table)->delete('id IN ('.implode(',', $ids).')');
		}

		return false;
	}

	public function deleteRelations($table, $items = array())
	{
		$ids = array();

		foreach ($items as $item) {
			if ($this->getByName($table)->params['is_system']) {
				foreach ($this->tables as $t) {
					if ($t->moduleName != 'user' && $t->moduleName != 'template' && $t->moduleName != 'page') {
						foreach ($t->fields as $field) {
							$ft = $t->getFieldType($field);

							if (stristr($ft->getParam('type'), 'select') && $ft->getParam('l_table') == $table) {
								$this->deleteItem($t->dbName(), $ft->getName().'='.$item['id']);
							}

							$ft->free();
						}
					}
				}
			}

			foreach ($this->getByName($table)->fields as $field) {
				$this->getByName($table)->getFieldType($field, $item)->free();
			}

			$ids[] = $item['id'];
		}

		return $ids;
	}
}