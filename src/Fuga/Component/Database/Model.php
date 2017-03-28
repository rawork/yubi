<?php

namespace Fuga\Component\Database;


abstract class Model
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

	public function getTable()
	{
		return $this->table;
	}

	public function getFields()
	{
		return $this->fields;
	}

	public function getOrderBy()
	{
		return $this->order_by;
	}

	public function getL10n()
	{
		return $this->l10n;
	}

	public function getActivate()
	{
		return $this->activate;
	}

	public function getSortable()
	{
		return $this->sortable;
	}

	public function getTreeLike()
	{
		return $this->treelike;
	}

	public function getSearchable()
	{
		return $this->searchable;
	}

	public function getModule()
	{
		return $this->module;
	}

}