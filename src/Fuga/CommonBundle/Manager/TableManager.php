<?php

namespace Fuga\CommonBundle\Manager;


use Fuga\Component\Database\CustomModel;
use Fuga\Component\Database\Model;
use Fuga\Component\Database\Table;
use Symfony\Component\Yaml\Yaml;

class TableManager extends ModelManager
{
	protected $tables = [];
	protected $config;

	protected function getConfig()
	{
		if (!$this->config) {
			$this->config = Yaml::parse(file_get_contents(PRJ_DIR.'/app/config/models.yml'));
		}

		return $this->config;
	}

	public function getAll()
	{
		if (empty($this->tables)) {

			// todo init table cache for prod
			$config = $this->getConfig();

			foreach ($config as $name => $table ){

				if (isset($table['model'])){
					list($vendor, $bundle, $model) = explode(':', $table['model']);
					$className = $vendor.'\\'.$bundle.'Bundle\\Model\\'.$model;
					if (class_exists($className)) {
						$model = new $className();
						if (!$model instanceof Model) {
							throw new \Exception('Класс модели не унаследован от Fuga\Component\Database\Model');
						}
						$this->tables[$name] = new Table($model, $this->container);
					}
				} else {
					$sql = "SELECT t.*, m.name as module
						FROM model t
						JOIN module m ON t.module_id=m.id
						WHERE t.publish=1 AND m.name = :module AND t.name = :table ORDER BY t.sort";
					$stmt = $this->container->get('connection')->prepare($sql);
					$stmt->bindValue("module", $table['module']);
					$stmt->bindValue("table", $name);
					$stmt->execute();
					while ($tableData = $stmt->fetch()) {

						$sql = "SELECT * FROM model_field WHERE publish=1 AND table_id= :id ORDER by sort";
						$stmt = $this->container->get('connection')->prepare($sql);
						$stmt->bindValue('id', $tableData['id']);
						$stmt->execute();
						$fields = $stmt->fetchAll();
						if ($fields) {
							foreach ($fields as &$field) {
								$field['group_update'] = $field['group_update'] == 1;
								$field['readonly'] = $field['readonly'] == 1;
								$field['search'] = $field['search'] == 1;
								$field['table_name'] = $name;
								if (!empty($field['params'])) {
									$params = json_decode(trim($field['params']), true);
									if (is_array($params)){
										foreach ($params as $key => $param) {
											$field[$key] = $param;
										}
									}
								}
								$this->fields[$field['name']] = $field;
							}
							unset($field);
							$tableData['table'] = $tableData['name'];
							$tableData['fields'] = $fields;
						} else {
							$this->container->get('log')->addError('В таблице '.$name.' не настроены поля');
						}

						$model = new CustomModel();
						$model->setOptions($tableData);

						$this->tables[$name] = new Table($model, $this->container);
					}
				}
			}
		}

		return $this->tables;
	}


	public function getByName($name)
	{
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
				$tables[$table->getName()] = $table;
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
			foreach ($this->tables as $t) {
				if ($t->moduleName != 'user' && $t->moduleName != 'template' && $t->moduleName != 'page') {
					foreach ($t->fields as $field) {
						$ft = $t->getFieldType($field);

						if (stristr($ft->getParam('type'), 'select') && $ft->getParam('l_table') == $table) {
							$this->deleteItem($t->getName(), $ft->getName().'='.$item['id']);
						}

						$ft->free();
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