<?php

namespace Fuga\CommonBundle\Model;


use Fuga\Component\Database\Model as BaseModel;

class Subscriber extends BaseModel
{
	protected $table = 'subscriber';
	protected $title = 'Подписчики';
	protected $module = 'subscribe';

	protected $order_by = 'created';

	protected $fields = [
		'email' => [
			'name'  => 'email',
			'title' => 'Адрес',
			'type'  => 'string',
			'width' => '24%',
			'search'=> true
		],
		'rubrics' => [
			'name' => 'rubrics',
			'title' => 'Тематики',
			'type' => 'select_list',
			'l_table' => 'subscribe_rubric',
			'l_field' => 'name',
			'view_type' => 'simple', // dialog
			'link_table' => 'subscriber_rubric',
			'link_inversed' => 'subscriber_id',
			'link_mapped' => 'rubric_id',
			'width' => '47%'
		],
		'hashkey' => [
			'name'  => 'hashkey',
			'title' => 'Активационный ключ',
			'type'  => 'string',
			'readonly' => true
		],
		'date' => [
			'name'  => 'date',
			'title' => 'Дата регистрации',
			'type'  => 'datetime',
			'width' => '24%',
			'search'=> true
		],
		'is_active' => [
			'name' => 'is_active',
			'title' => 'Акт.',
			'type' => 'checkbox',
			'search' => true,
			'group_update'  => true,
			'width' => '1%'
		]
	];
}
