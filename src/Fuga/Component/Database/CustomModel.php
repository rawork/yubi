<?php

namespace Fuga\Component\Database;


class CustomModel extends Model
{
	protected $id;
	protected $table;
	protected $title;
	protected $fields = [];

	protected $order_by = 'id';
	protected $l10n = false;
	protected $activate = false;
	protected $sortable = false;
	protected $treelike = false;
	protected $searchable = false;

	protected $module;

	public function getTitle()
	{
		return $this->title;
	}

	public function setOptions($options)
	{
		foreach ($options as $name => $value) {
			$this->$name = $value;
//			if (property_exists('CustomModel', $name)) {
//
//			}
		}
	}

}