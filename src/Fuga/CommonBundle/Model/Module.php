<?php

namespace Fuga\CommonBundle\Model;


use Fuga\Component\Database\Model as BaseModel;

class Module extends BaseModel
{
	protected $table = 'module';
	protected $title = 'Модули';
	protected $module = 'config';

	protected $order_by = 'sort,title';
	protected $sortable = true;

	protected $fields = [
		'title' => array (
			'name' => 'title',
			'title' => 'Название',
			'type' => 'string',
			'width' => '25%'
		),
		'name' => array (
			'name' => 'name',
			'title' => 'Сист. имя',
			'type' => 'string',
			'width' => '20%',
			'search' => true
		),
		'path' => array (
			'name' => 'path',
			'title' => 'Путь',
			'type' => 'string',
			'width' => '50%',
			'search' => true
		),
		'sort' => array (
			'name' => 'sort',
			'title' => 'Сорт.',
			'type' => 'number',
			'group_update'  => true,
			'width' => '3%'
		)
	];
}