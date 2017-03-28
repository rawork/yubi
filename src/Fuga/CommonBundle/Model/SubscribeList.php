<?php

namespace Fuga\CommonBundle\Model;

use Fuga\Component\Database\Model as BaseModel;

class SubscribeList extends BaseModel
{
	protected $table = 'subscribe_list';
	protected $title = 'Очередь рассылки';
	protected $module = 'subscribe';

	protected $order_by = 'date';

	protected $fields = [
		'rubrics' => [
			'name' => 'rubrics',
			'title' => 'Списки рассылки',
			'type' => 'select_list',
			'l_table' => 'subscribe_rubric',
			'l_field' => 'name',
			'width' => '30%'
		],
		'subject' => [
			'name' => 'subject',
			'title' => 'Тема',
			'type' => 'string',
			'width' => '35%',
			'search'=> true
		],
		'message' => [
			'name' => 'message',
			'title' => 'Текст',
			'type' => 'html'
		],
		'file' => [
			'name' => 'file',
			'title' => 'Файл',
			'type' => 'file',
			'path' => '/mailfiles',
			'width' => '20%'

		],
		'date' => [
			'name' => 'date',
			'title' => 'Дата',
			'type' => 'datetime',
			'width' => '15%',
			'search'=> true
		]
	];
}
