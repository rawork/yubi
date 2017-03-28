<?php

namespace Fuga\CommonBundle\Model;


use Fuga\Component\Database\Model as BaseModel;

class User extends BaseModel
{
	protected $table = 'user';
	protected $title = 'Список пользователей';
	protected $module = 'user';

	protected $order_by = 'lastname,name,login';

	protected $fields = [
		'login' => array (
			'name' => 'login',
			'title' => 'Логин',
			'type' => 'string',
			'width' => '20%',
			'search' => true,
		),
		'password' => array (
			'name' => 'password',
			'title' => 'Пароль',
			'type' => 'password',
		),
		'token' => array (
			'name' => 'token',
			'title' => 'Токен',
			'type' => 'string',
			'readonly' => true,
		),
		'hashkey' => array (
			'name' => 'hashkey',
			'title' => 'Ключ',
			'type' => 'string',
			'readonly' => true,
		),
		'name' => array (
			'name' => 'name',
			'title' => 'Имя',
			'type' => 'string',
			'width' => '20%',
			'search' => true,
		),
		'lastname' => array (
			'name' => 'lastname',
			'title' => 'Фамилия',
			'type' => 'string',
			'width' => '20%',
			'search' => true,
		),
		'email' => array (
			'name' => 'email',
			'title' => 'Эл. почта',
			'type' => 'string',
			'width' => '15%',
			'search' => true,
		),
		'group_id' => array (
			'name' => 'group_id',
			'title' => 'Группа',
			'type' => 'select',
			'l_table' => 'user_group',
			'l_field' => 'title',
			'width' => '15%',
			'search' => true,
		),
		'is_admin' => array (
			'name' => 'is_admin',
			'title' => 'Админ',
			'type' => 'checkbox',
			'width' => '1%',
			'group_update' => true
		),
		'is_active' => array (
			'name' => 'is_active',
			'title' => 'Активен',
			'type' => 'checkbox',
			'width' => '1%',
			'group_update' => true,
			'search' => true,
		)
	];
}