<?php

namespace Fuga\Component\DB\Field;


class DateType extends Type
{
	protected $type = 'date';

	public function __construct($params, $entity = null)
	{
		parent::__construct($params, $entity);
	}

	public function getSQLValue($name = '')
	{
		$value = $this->getValue($name);
		if (in_array($value, array(null, '0000-00-00'))) {
			return "0000-00-00";
		}

		return $value;
	}

	public function getStatic()
	{
		return !in_array($this->dbValue, array(null, '0000-00-00')) ? $this->get('util')->format_date($this->dbValue, 'j F Y', false) : '';
	}

	public function getInput($value = '', $name = '')
	{
		return $this->dateType_getInput(($name ?: $this->getName()), $value?: $this->dbValue);
	}

	public function getSearchInput()
	{
		$dateFrom = '';
		$dateTill = '';

		if ($this->getSearchValue('from')) {
			$dateFrom = $this->getSearchValue('from');
		}
		if ($this->getSearchValue('till')) {
			$dateTill = $this->getSearchValue('till');
		}

		return '<div class="form-inline">
			<div class="input-group">
			<div class="input-group-addon">C</div>' .
			$this->dateType_getInput(parent::getSearchName('from'), $dateFrom, false) . '
			<div class="input-group-addon">По</div>' .
			$this->dateType_getInput(parent::getSearchName('till'), $dateTill, false) . '
			</div> 
		</div>';
	}

	public function getSearchSQL()
	{
		$ret = '';
		if ($date = $this->getSearchValue('from')) {
			$ret = $this->getName() . ">='$date'";
		}
		if ($date = $this->getSearchValue('till')) {
			$ret .= ($ret ? ' AND ' : '') . $this->getName() . "<='$date'";
		}
		return $ret;
	}

	public function getSearchURL($name = '')
	{
		$ret = parent::getSearchURL('from');

		if ($date = parent::getSearchURL('till')) {
			$ret .= ($ret ? '&' : '') . $date;

		}

		return $ret;
	}

	public function dateType_getInput($name, $value = false, $fill = true)
	{
		$date = '';
		if ($value && '0000-00-00' != $value) {
			$date = substr($value, 0, 10);
		}

		return '
	<input class="form-control field-date" type="text" placeholder="Выберите дату..." data-value="' . $date . '" name="' . $name . '" id="' . $name . '">
	';
	}
	
}
