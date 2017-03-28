<?php

namespace Fuga\CommonBundle\Model;


use Fuga\Component\Database\Model as BaseModel;

class Variable extends BaseModel
{
	
	protected $table = 'variable';
	protected $title = 'Общие настройки';
	protected $module = 'config';

	protected $order_by = 'name';

	protected $fields = [
		'title' => [
			'name' => 'title',
			'title' => 'Название',
			'type' => 'string',
			'width' => '33%',
			'search' => true
		],
		'name' => [
			'name' => 'name',
			'title' => 'Имя (англ.)',
			'type' => 'string',
			'width' => '33%',
			'search'=> true
		],
		'value' => [
			'name'  => 'value',
			'title' => 'Значение',
			'width' => '33%',
			'type' => 'string'
		]
	];
}
