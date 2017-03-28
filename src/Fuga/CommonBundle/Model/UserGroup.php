<?php

namespace Fuga\CommonBundle\Model;


use Fuga\Component\Database\Model as BaseModel;

class UserGroup extends BaseModel
{
	protected $table = 'user_group';
	protected $title = 'Группы пользователей';
	protected $module = 'user';

	protected $order_by = 'title';

	protected $fields = [
		'title' => [
			'name' => 'title',
			'title' => 'Название',
			'type' => 'string',
			'width' => '15%',
		],
		'name' => [
			'name' => 'name',
			'title' => 'Системное имя',
			'type' => 'string',
			'width' => '15%',
			'help' => 'англ. буквы без пробелов',
			'search' => true,
		],
		'rules' => [
			'name' => 'rules',
			'title' => 'Доступ к модулям',
			'type' => 'select_list',
			'l_table' => 'module',
			'l_field' => 'title',
			'view_type' => 'simple', // dialog
			'link_table' => 'user_group_module',
			'link_inversed' => 'group_id',
			'link_mapped' => 'module_id',
			'width' => '65%',
			'search' => true,
		]
	];
}