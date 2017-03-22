<?php

namespace Fuga\Component\DB\Field;

class EnumType extends Type
{
	public function __construct(&$params, $entity = null)
	{
		parent::__construct($params, $entity);
	}

	public function enum_getInput($value, $name)
	{
		$value = $value ?: $this->dbValue;
		$sel = '';
		$ret = '<select class="form-control select-'.$name.'" '.$sel.' name="'.$name.'">';

		if ($this->getParam('select_values')) {
			$items = json_decode($this->getParam('select_values'), true);

			foreach ($items as $key => $item) {
				if (is_numeric($key)) {
					$ret .= '<option '.($value == $item ? 'selected ' : '').'value="'.$item.'">'.$item.'</option>';
				} else {
					$ret .= '<option '.($value == $key ? 'selected ' : '').'value="'.$key.'">'.$item.'</option>';
				}
			}
		}

		$ret .= '</select>';

		return $ret;
	}

	public function getStatic()
	{
		if ($this->getParam('select_values')) {
			$svalues = json_decode($this->getParam('select_values'), true);

			foreach ($svalues as $key => $value) {
				if ($key == $this->dbValue) {
					return $value;
				}
			}	
		}

		return $this->dbValue;
	}

	public function getInput($value = '', $name = '')
	{
		return $this->enum_getInput(($value ?: $this->dbValue), ($name ?: $this->getName()));
	}

	public function getSearchInput()
	{
		return $this->enum_getInput(parent::getSearchValue(), parent::getSearchName());
	}

}
