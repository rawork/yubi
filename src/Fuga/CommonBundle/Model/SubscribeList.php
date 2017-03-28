<?php

namespace Fuga\CommonBundle\Model;

class SubscribeList
{
	protected $table = 'subscribe_list';
	protected $title = 'Очередь рассылки';
	protected $module = 'subscribe';

	protected $order_by = 'date';

	protected $fields = [
		'rubrics' => array (
			'name' => 'rubrics',
			'title' => 'Списки рассылки',
			'type' => 'select_list',
			'l_table' => 'subscribe_rubric',
			'l_field' => 'name',
			'width' => '30%'
		),
		'subject' => array (
			'name' => 'subject',
			'title' => 'Тема',
			'type' => 'string',
			'width' => '35%',
			'search'=> true
		),
		'message' => array (
			'name' => 'message',
			'title' => 'Текст',
			'type' => 'html'
		),
		'file' => array (
			'name' => 'file',
			'title' => 'Файл',
			'type' => 'file',
			'path' => '/mailfiles',
			'width' => '20%'

		),
		'date' => array (
			'name' => 'date',
			'title' => 'Дата',
			'type' => 'datetime',
			'width' => '15%',
			'search'=> true
		)
	];
}
