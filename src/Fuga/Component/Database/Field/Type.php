<?php

namespace Fuga\Component\Database\Field;

class Type
{
	protected $params = [];
	protected $dbValue = null;
	protected $dbId = 0;
	protected $type = 'string';
	
	public function __construct($params, $entity = null)
	{
		$this->setParams($params);
		$this->setEntity($entity);
	}

	public function setParams($params = array())
	{
		$this->params = $params;

		if ($this->getParam('l_field') && !$this->getParam('l_sort')) {
			$this->setParam('l_sort', $this->getParam('l_field'));
		}
		if ($sizes = $this->getParam('sizes')) {
			$sizes["default"] = ["width" => 50, "height" => 50, "adaptive" => true];
			$this->setParam('sizes', $sizes);
		} else {
			$this->setParam('sizes', []);
		}
		if (!$this->getParam('link_type')) {
			$this->setParam('link_type', 'one');
		}
		if (!$this->getParam('view_type')) {
			$this->setParam('view_type', 'simple');
		}
	}
	
	public function setParam($name, $value)
	{
		$this->params[$name] = $value;
	}
	
	public function getParam($name)
	{
		return isset($this->params[$name])? $this->params[$name] : null;
	}

	public function setEntity($entity = null)
	{
		$this->dbId = null;
		$this->dbValue = null;

		if (is_array($entity) && isset($entity['id'])) {
			$this->dbId		= (int)$entity['id'];
			$this->dbValue	= isset($entity[$this->getName()]) ? $entity[$this->getName()] : '';
		} elseif ($this->getParam('defvalue')) {
//			$this->dbValue	= $this->getParam('defvalue');
		}
	}

	public function getName()
	{
		return $this->getParam('name');
	}

	public function getGroupInput()
	{
		return $this->getInput('', $this->getName().$this->dbId);
	}
	
	public function getGroupStatic()
	{
		return $this->getStatic();
	}

	public function getGroupSQLValue()
	{
		return $this->getSQLValue($this->getName().$this->dbId);
	}

	// todo these methods must be protected
	public function getSearchName($subName = '')
	{
		return 'search_filter_'.$this->getName().($subName ? '_'.$subName : '');
	}

	public function getValue($name = '')
	{
		$name = $name ?: $this->getName();
//		$value = isset($_REQUEST[$name]) ? $_REQUEST[$name] : ($this->dbValue ?: null);
		$value = $this->get('request')->request->get($name, $this->dbValue);

		return $value;
	}

	public function getSearchValue($subName = '')
	{
		return $this->getValue($this->getSearchName($subName));
	}

	public function getSQLValue($name = '')
	{
		return $this->getValue($name);
	}
	
	public function getNativeValue()
	{
		return $this->dbValue;
	}

	public function getStatic()
	{
		$ret = strip_tags(trim($this->dbValue));
		return $ret ?: '&nbsp;';
	}

	public function getInput($value = '', $name = '')
	{
		$name = $name ? $name : $this->getName();
		$value = $value ? str_replace('"', '&quot;', $value) : str_replace('"', '&quot;', $this->dbValue);
		
		return '<input type="text" class="form-control" name="'.$name.'" value="'.$value.'" >';
	}

	public function getSearchInput()
	{
		return $this->getInput($this->getSearchValue(), $this->getSearchName());
	}

	public function getSearchSQL()
	{
		if ($value = $this->getSearchValue()) {
			return $this->getName()." LIKE '%".$value."%'";
		}
		
		return '';
	}

	public function getSearchURL($name = '')
	{
		if ($value = $this->getSearchValue($name)) {
			return $this->getSearchName($name).'='.$value;
		}
		
		return '';
	}
	
	public function free(){}

	public function get($name)
	{
		global $container;

		if ($name == 'container') {
			return $container;
		} else {
			return $container->get($name);
		}
	}

	public function getType()
	{
		return $this->type;
	}
}
