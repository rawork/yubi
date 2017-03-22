<?php

namespace Fuga\Component\DB\Field;

class DatetimeType extends Type
{
	protected $type = 'datetime';

	public function __construct(&$params, $entity = null)
	{
		parent::__construct($params, $entity);
	}

	public function getValue($name = '')
	{
		$name = $name ?: $this->getName();
		$value = $this->get('request')->request->get($name);
		if ($value && $time = $this->get('request')->request->get($name . '_time')) {
			$value .= ' ' . $time . ':00';
		}

		return $value;
	}

	public function getSQLValue($name = '')
	{
		$value = $this->getValue($name);
		if (in_array($value, array(null, '0000-00-00 00:00:00'))) {
			return "0000-00-00 00:00:00";
		}

		return $value;
	}

	public function getStatic()
	{
		return !in_array($this->dbValue, array(null, '0000-00-00 00:00:00')) ? $this->get('util')->format_date($this->dbValue, 'j F Y, H:i', false) : '';
	}

	public function getInput($value = '', $name = '')
	{
		return $this->dateType_getInput(($name ? $name : $this->getName()), $this->dbValue);
	}

	public function getSearchInput()
	{
		$dateFrom = '';
		$dateTill = '';

		if ($this->getSearchValue('from')) {
			$dateFrom = $this->getSearchValue('from') . ' ' . $this->getSearchValue('from_time');
		}
		if ($this->getSearchValue('till')) {
			$dateTill = $this->getSearchValue('till') . ' ' . $this->getSearchValue('till_time');
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
		$ret = '';
		if ($ret = parent::getSearchURL('from')) {
			if ($time = $this->getSearchValue($this->getSearchName('from_time'))) {
				$ret .= ' ' . $time;
			}
		}
		if ($date = parent::getSearchURL('till')) {
			$ret .= ($ret ? '&' : '') . $date;
			if ($time = $this->getSearchValue($this->getSearchName('till_time'))) {
				$ret .= ' ' . $time;
			}
		}

		return $ret;
	}

	public function dateType_getInput($name, $value = false, $fill = true)
	{
		$date = '';
		$time = '';
		if ($value && '0000-00-00 00:00:00' != $value) {
			$date = substr($value, 0, 10);
			$time = substr($value, 11, 5);
		}

		return '
	<input class="form-control field-date" type="text" placeholder="Выберите дату..." data-value="' . $date . '" name="' . $name . '" id="' . $name . '">
	<input class="form-control field-time" type="text" placeholder="Выберите время..." value="' . $time . '" name="' . $name . '_time" id="' . $name . '_time">
	';
	}

}