<?php

namespace Fuga\Component\Database;
	
use Fuga\Component\Container;

class Table
{
	public $name;
	public $title;
	public $fields;
	public $params;
	public $moduleName;
	public $tableName;

	protected $stmt;
	protected $filedtypes = [];
	protected $container;
	protected $model;

	public function __construct(Model $model, Container $container)
	{
		$this->model 		= $model;
		$this->container 	= $container;
		$this->name 		= $this->model->getTable();
		$this->tableName	= $this->model->getTable();
		$this->title 		= $this->model->getTitle();
		$this->moduleName	= $this->model->getModule();
		$this->fields 		= $this->model->getFields();

		$params = [];
		$params['l10n']			= $this->model->getL10n();
		$params['sortable']		= $this->model->getSortable();
		$params['activate']		= $this->model->getActivate();
		$params['order_by']		= $this->model->getOrderBy();
		$params['searchable']	= $this->model->getSearchable();
		$params['treelike']	= $this->model->getTreeLike();
		$params['rpp']			= 25;

		$this->params = $params;
		$this->setTableFields();
	}

	public function getModel()
	{
		return $this->model;
	}
	
	public function getName()
	{
		return $this->tableName;
	}
	
	public function getFieldType($field, $entity = null)
	{
		if (empty($this->filedtypes[$field['type']])) {
			switch ($field['type']) {
				case 'select_tree':
					$fieldName = 'SelectTree';
					break;
				case 'select_list':
					$fieldName = 'SelectList';
					break;
				default:	
					$fieldName = ucfirst($field['type']);
					break;
			}
			$className = '\\Fuga\\Component\\Database\\Field\\'.$fieldName.'Type';
			$this->filedtypes[$field['type']] = new $className($field);
		}
		$this->filedtypes[$field['type']]->setParams($field);
		$this->filedtypes[$field['type']]->setEntity($entity);
		
		return $this->filedtypes[$field['type']];
	}

	public function getFieldList()
	{
		$ret = array('id');
		foreach ($this->fields as $field) {
			if (in_array($field['type'], array('gallery'))) {
				continue;
			}
			$ret[] = $field['name'];
		}
		
		return $ret;
	}

	public function insertGlobals()
	{
		$extraIds = array();
		$values = array();
		foreach ($this->fields as $field) {
			if (in_array($field['type'], array('gallery', 'select_list'))) {
				continue;
			}	
			$fieldType = $this->getFieldType($field);
			switch ($field['name']) {
				case 'created':
					$values[$fieldType->getName()] = date('Y-m-d H:i:s');
					break;
				case 'locale':
					$values[$fieldType->getName()] = $this->container->get('session')->get('locale');
					break;
				default:
					$values[$fieldType->getName()] = $fieldType->getSQLValue();
			}
			if (in_array($field['type'], array('select', 'select_tree'))
				&& isset($field['link_type'])
				&& $field['link_type'] == 'many'
				) {
				$extraIds = explode(',', $this->container->get('request')->request->get($field['name'].'_extra'));
				$extraIds[] = $fieldType->getSQLValue();
				$linkTable = $field['link_table'];
				$linkInversed = $field['link_inversed'];
				$linkMapped = $field['link_mapped'];
			}
		}
		if ($lastId = $this->insert($values)) {
			foreach ($extraIds as $extraId) {
				$this->container->get('connection')->insert(
					$linkTable,
					array($linkInversed => $lastId, $linkMapped => $extraId)
				);
			}
			
			return $lastId;
		} else {
			return false;
		}
	}
	
	public function updateGlobals()
	{
		$entityId = $this->container->get('request')->request->get('id', true);
		$entity = $this->getItem($entityId);
		$values = array();
		foreach ($this->fields as $field) {
			$fieldType = $this->getFieldType($field, $entity);
			switch ($field['name']) {
				case 'updated':
					$values[$fieldType->getName()] = date('Y-m-d H:i:s');
					break;
				default:
					if ($field['type'] == 'gallery') {
						$fieldType->getSQLValue();
						break;
					}
					if (empty($field['readonly'])) {
						$values[$fieldType->getName()] = $fieldType->getSQLValue();
					}
			}

			if (in_array($field['type'], array('select', 'select_tree'))
				&& isset($field['link_type']) && $field['link_type'] == 'many'
				) {
				$extraIds = explode(',', $this->container->get('request')->request->get($field['name'].'_extra'));
				$extraIds[] = $fieldType->getSQLValue();
				$linkTable = $field['link_table'];
				$linkInversed = $field['link_inversed'];
				$linkMapped = $field['link_mapped'];
				$this->container->get('connection')->delete($linkTable, array($linkInversed => $entityId));
				foreach ($extraIds as $extraId) {
					$this->container->get('connection')->insert($linkTable, 
							array($linkInversed => $entityId, $linkMapped => $extraId)
					);
				}
			}
		}

		return $this->update($values, array('id' => $entityId));
	}

	function group_update()
	{
		$this->select(array('where' => 'id IN('.$this->container->get('request')->request->get('ids').')'));
		$entities = $this->getNextArrays();
		foreach ($entities as $entity) {
			$values = array();
			$entityId = $entity['id'];
			foreach ($this->fields as $field) {
				if ($field['type'] == 'gallery') {
					$fieldType = $this->getFieldType($field, $entity);
					$fieldType->getSQLValue();
				} else {
					$fieldType = $this->getFieldType($field, $entity);
					if ('checkbox' == $field['type'] && isset($field['group_update']) && true == $field['group_update']) {
						$values[$fieldType->getName()] = $fieldType->getGroupSQLValue();
					}
					if ($this->container->get('request')->request->get($fieldType->getName().$entity['id'])
						|| isset($_FILES[$fieldType->getName().$entity['id']])) {
						$values[$fieldType->getName()] = $fieldType->getGroupSQLValue(); 
					}	
				}
				
				if (($field['type'] == 'select' || $field['type'] == 'select_tree')
					&& isset($field['link_type']) && $field['link_type'] == 'many'
					) {
					$extraIds = explode(',', $this->container->get('request')->request->get($field['name'].$entityId.'_extra'));
					$linkTable = $field['link_table'];
					$linkInversed = $field['link_inversed'];
					$linkMapped = $field['link_mapped'];
					$this->container->get('connection')->delete($linkTable, array($linkInversed => $entityId));
					foreach ($extraIds as $extraId) {
						$this->container->get('connection')->insert($linkTable, array(
							$linkInversed => $entityId,
							$linkMapped => $extraId
						));
					}
				}
			}
			if ($values) {
				$this->update($values, array('id' => $entity['id']));
			}	
		}
		return true;
	}

	public function getOptions($type) {
		$options = array();
		switch ($type) {
			case 'money':
				$options['default'] = 0;
				return $options;
			case 'integer':
				$options['default'] = 0;
				return $options;
			case 'boolean':
				$options['default'] = 0;
				return $options;
			case 'datetime':
				$options['default'] = '0000-00-00 00:00:00';
				return $options;
			case 'date':
				$options['default'] = '0000-00-00';
				return $options;
			default:
				$options['default'] = '';
				return $options;
		}
	}
	
	public function getSchema()
	{
		$schema = new \Doctrine\DBAL\Schema\Schema();
		$table = $schema->createTable($this->getName());
		$table->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => true));
		foreach ($this->fields as $field) {
			$type = $this->getFieldType($field)->getType();
			$options = $this->getOptions($type);

			$table->addColumn($field['name'], $type, $options);
		}
		$table->setPrimaryKey(array('id'));
		return $schema;
	}

	public function create()
	{
		try {
			$queries = $this->getSchema()->toSql($this->container->get('connection')->getDatabasePlatform());

			foreach ($queries as $sql) {
				$this->container->get('connection')->query($sql);
			}

			return true;
		} catch (\Exception $e) {
			$this->container->get('log')->addError($e->getMessage());
			$this->container->get('log')->addError($e->getTraceAsString());

			return false;
		}	
	}
	
	public function alter()
	{
		try {
			$sm = $this->container->get('connection')->getSchemaManager();
			$fromSchema = $sm->createSchema();
			$toSchema = clone $fromSchema;
			$table = $toSchema->getTable($this->getName());

			foreach ($this->fields as $field) {
				if (in_array($field['type'], array('gallery'))) {
					continue;
				}

				$type = $this->getFieldType($field)->getType();

				try {
					$column = $table->getColumn($field['name']);
					if ($column->getType()->getName() != $type && 'id' != $column->getName()) {
						$this->container->get('log')->addError($field['type']);
						$table->changeColumn(
							$field['name'], 
							array_merge(array('type' => \Doctrine\DBAL\Types\Type::getType($type), $this->getOptions($type)))
						);
					}
				} catch (\Exception $e) {
					$table->addColumn($field['name'], $type, $this->getOptions($type));
				}
			}
			$columns = $table->getColumns();

			foreach ($columns as $column) {
				if ('id' == $column->getName()) {
					continue;
				}	

				if (!isset($this->fields[$column->getName()])){
					$table->dropColumn($column->getName());
				}
			}

			// TODO Написать создание уникальных индексов по описанию
			// TODO Написать создание индексов по описанию
			if ($this->params['searchable']) {
				// TODO Заново написать создание индексов для поиска
			}
			
			$queries = $fromSchema->getMigrateToSql($toSchema, $this->container->get('connection')->getDatabasePlatform());
			foreach ($queries as $sql) {
				$this->container->get('log')->addError($sql);
				$this->container->get('connection')->query($sql);
			}
			
			return true;
		} catch (\Exception $e) {
			$this->container->get('log')->addError($e->getMessage());
			$this->container->get('log')->addError($e->getTraceAsString());

			return false;
		}
		
	}

	public function copy($id, $times = 1)
	{
		$entity = $this->getItem($id);

		if ($entity) {
			for ($i = 1; $i <= $times; $i++) {
				$this->insertArray($entity);
			}

			return true;
		}

		return false;
	}
	
	public function drop()
	{
		return $this->container->get('connection')->query('DROP TABLE '.$this->getName());
	}
	
	public function truncate()
	{
		return $this->container->get('connection')->query('TRUNCATE TABLE '.$this->getName());
	}
	
	public function getSearchSQL()
	{
		$filters = array();
		$value = $this->container->get('request')->request->getInt('search_filter_id');
		if ($value) {
			$filters[] = 'id='.$value;
		}
		foreach ($this->fields as $field) {
			$fieldType = $this->getFieldType($field);
			if ($filter = $fieldType->getSearchSQL()) {
				$filters[] = $filter;
			}
		}
		return implode(' AND ', $filters);
	}

	public function getSearchURL($request)
	{
		$filters = array();
		$value = $request->request->getInt('search_filter_id');
		if ($value) {
			$filters[] = 'search_filter_id='.$value;
		}
		foreach ($this->fields as $field) {
			$fieldType = $this->getFieldType($field);
			if ($filter = $fieldType->getSearchURL()) {
				$filters[] = $filter;
			}
		}
		return implode('&', $filters);
	}
	
	public function insert($values)
	{
		if (!array_key_exists('created', $values)) {
			$values['created'] = date('Y-m-d H:i:s');
		}
		
		if (!array_key_exists('updated', $values)) {
			$values['updated'] = '0000-00-00 00:00:00';
		}
		
		if ($this->container->get('connection')->insert($this->getName(), $values)) {
			$lastId = $this->container->get('connection')->lastInsertId();
			$this->updateNested();
			
			return $lastId;
		}
		
		return false;
	}
	
	function insertArray($entity)
	{
		$values = array();
		$entity['created'] = date('Y-m-d H:i:s');
		$entity['updated'] = '0000-00-00 00:00:00';
		foreach ($this->fields as $field) {
			foreach ($entity as $fieldName => $fieldValue) {
				if (empty($fieldValue)) {
					continue;
				}
				$fieldType = $this->getFieldType($field);
				if ($fieldType->getName() == $fieldName) {
					if ($field['type'] == 'template') {
						$fileInfo = pathinfo($fieldValue);
						$dest = $this->container->get('util')->getNextFileName($fileInfo['basename'], $fileInfo['dirname']);
						@copy(PRJ_DIR.$fieldValue, PRJ_DIR.$fileInfo['dirname'].'/'.$dest);
						$values[$fieldType->getName()] = $fileInfo['dirname'].'/'.$dest;
					} elseif ($field['type'] == 'file') {
						$fileInfo = pathinfo($fieldValue);
						$values[$fieldType->getName()] = $this->container->get('filestorage')->save($fileInfo['basename'], PRJ_DIR.UPLOAD_REF.$fieldValue);
					} elseif ($field['type'] == 'image') {
						$fileInfo = pathinfo($fieldValue);
						$this->container->get('imagestorage')->setOptions(['sizes' => $fieldType->getParam('sizes')]);
						$values[$fieldType->getName()] = $this->container->get('imagestorage')->save($fileInfo['basename'], PRJ_DIR.UPLOAD_REF.$fieldValue);
					} else {
						$values[$fieldType->getName()] = $fieldValue;
					}
					break;
				}
			}
		}
		$lastId = $this->insert($values);

		return true;
	}

	public function update(array $values, array $criteria)
	{
		$ret = $this->container->get('connection')->update($this->getName(), $values, $criteria);
		$this->updateNested();

		return $ret;
	}
	
	private function updateNested($parentId = 0, $level = 1, $left_key = 0)
	{
		if (empty($this->params['treelike'])) {
			return;
		}
		$table = $this->getName();
		$sql = "SELECT id FROM $table WHERE parent_id= :id ".($this->params['l10n'] ? 'AND locale= :locale' : '')." ORDER BY sort";
		$stmt = $this->container->get('connection')->prepare($sql);
		$stmt->bindValue('id', $parentId);
		$stmt->bindValue('locale', $this->container->get('session')->get('locale'));
		$stmt->execute();
		$items = $stmt->fetchAll();

		if ($items) {
			foreach ($items as $item) {
				$left_key++;
				$right_key = $this->updateNested($item['id'], $level+1, $left_key);
				$this->container->get('connection')->update($table,
					array(
						'left_key' => $left_key, 
						'right_key' => $right_key, 
						'level' => $level,
					),	
					array('id' => $item['id'])
				);
				$left_key = $right_key;
			}
		} else {
			$right_key = $left_key;
		}

		return ++$right_key;
	}
	
	public function delete($criteria)
	{
		return $this->container->get('connection')->query('DELETE FROM '.$this->getName().' WHERE '.$criteria);
	}
	
	public function select($options = array())
	{
		try {
			if ($this->params['l10n']) {
				$locale = $this->container->get('session')->get('locale');
				$options['where'] = empty($options['where']) ? 
						"locale='".$locale."'" 
						: 
						$options['where']." AND locale='".$locale."'";
			}
			if (empty($options['select'])) {
				$options['select'] = implode(',', $this->getFieldList());
			}
			if (empty($options['from'])) {
				$options['from'] = $this->getName();
			}
			if (empty($options['where'])) {
				$options['where'] = '1=1';
			}
			if (empty($options['order_by'])) {
				$options['order_by'] = $this->params['order_by'] ?: 'id';
			}
			if (empty($options['limit'])) {
				$options['limit'] = '10000';
			}
			$sql = 'SELECT '.$options['select'].
				' FROM '.$options['from'].
				' WHERE '.$options['where'].
				' ORDER BY '.$options['order_by'].
				' LIMIT '.$options['limit'];

			$this->stmt = $this->container->get('connection')->prepare($sql);
			$this->stmt->execute();

//			$this->container->get('log')->addError($sql);
			
			return true;	
		} catch (\Exception $e) {
			$this->container->get('log')->addError($sql);
			$this->container->get('log')->addError($e->getMessage());

			return false;
		}	
	}
	
	public function getNextArray($detailed = true)
	{
		$entity = $this->stmt->fetch();
		if ($detailed && $entity) {
			foreach ($this->fields as $field) {
				$entity[$this->getFieldType($field, $entity)->getName().'_value'] = $this->getFieldType($field, $entity)->getNativeValue();
			}
		}
		return $entity;
	}
	
	public function getNextArrays($detailed = true)
	{
		$items = array();
		while ($item = $this->getNextArray($detailed)) {
			if (isset($item['id'])) {
				$items[$item['id']] = $item;
			} else {
				$items[] = $item;
			}
		}	
		
		return $items;
	}
	
	public function getItem($criteria, $sort = '', $select = '', $detailed = true)
	{
		$criteria = is_numeric($criteria) ? 'id='.$criteria : $criteria;
		$this->select(array('where' => $criteria, 'select' => $select, 'order_by' => $sort, 'limit' => 1));
		return $this->getNextArray($detailed);    
	}

	public function getItems($criteria = null, $sort = null, $limit = null, $select = null, $detailed = true)
	{
		$options = array('where' => $criteria, 'order_by' => $sort, 'limit' => $limit, 'select' => $select);
		$this->select($options);

		return $this->getNextArrays($detailed);
	}
	
	public function getPrev($id, $parent = 'parent_id')
	{
		$ret = array();
		$node = $this->getItem($id, '', '', false);
		if ($node) {
			$ret = $this->getPrev($node[$parent], $parent);
			$ret[] = $node;
		}
		
		return $ret;
	}

	function count($criteria = '')
	{
		try {
			$this->select(array(
				'select' => 'COUNT(id) as quantity', 
				'where' => $criteria
			));
			$quantity = $this->stmt->fetchColumn();
		} catch (\Exception $e) {
			$this->container->get('log')->addError($e->getMessage());
			$quantity = 0;
		}
		
		return $quantity ? (int)$quantity : 0;
	}

	private function setTableFields()
	{
		if ($this->params['sortable']) {
			$this->fields['sort'] = array(
				'name' => 'sort',
				'title' => 'Сорт.',
				'type' => 'number',
				'width' => '10%',
				'defvalue' => '500',
				'group_update' => true
			);
		}
		if ($this->params['activate']) {
			$this->fields['publish'] = array (
				'name' => 'publish',
				'title' => 'Акт.',
				'type' => 'checkbox',
				'search' => true,
				'group_update'  => true,
				'width' => '1%'
			);
		}
		if ($this->params['l10n']) {
			$this->fields['locale'] = array (
				'name'  => 'locale',
				'title' => 'Локаль',
				'type'  => 'string',
				'readonly' => true
			);
		}
		$this->fields['created'] = array (
			'name'  => 'created',
			'title' => 'Дата создания',
			'type'  => 'datetime',
			'readonly' => true
		);
		$this->fields['updated'] = array (
			'name'  => 'updated',
			'title' => 'Дата изменения',
			'type'  => 'datetime',
			'readonly' => true
		);

		foreach ($this->fields as &$field) {
			$field['table'] = $this->getName();
		}
	}
	
	public static function fillValue(&$value, $key)
	{
		$value = "'".$value."'";
	}
	
}
