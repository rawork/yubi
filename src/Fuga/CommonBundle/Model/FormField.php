<?php

namespace Fuga\CommonBundle\Model;

use Fuga\Component\Database\Model as BaseModel;

class FormField extends BaseModel {
	
	protected $table = 'form_field';
	protected $title = 'Поля формы';
	protected $module = 'form';

	protected $order_by = 'sort,name';
	protected $sortable = true;
	protected $l10n = true;

	protected $fields = [
		'title' => [
			'name' => 'title',
			'title' => 'Название',
			'type' => 'string',
			'width' => '25%'
		],
		'name' => [
			'name' => 'name',
			'title' => 'Имя (англ.)',
			'type' => 'string',
			'width' => '25%',
			'search' => true
		],
		'form_id' => [
			'name' => 'form_id',
			'title' => 'Форма',
			'type' => 'select',
			'l_table' => 'form',
			'l_field' => 'title',
			'l_lang' => true,
			'width' => '25%',
			'search' => true
		],
		'type' => [
			'name' => 'type',
			'title' => 'Тип',
			'type' => 'enum',
			'select_values' => '{"text":"Строка","textarea":"Текст","choice":"Список","email":"Почта","checkbox":"Флажок","file":"Файл","password":"Пароль"}',
			'width' => '15%'
		],
		'options' => [
			'name' => 'options',
			'title' => 'Настройки поля',
			'type' => 'text',
			'help' => 'Настройки поля с формате JSON'
		],
		'is_required' => [
			'name' => 'is_required',
			'title' => 'Обяз.',
			'type' => 'checkbox',
			'group_update'  => true,
			'width' => '1%'
		]
	];
}
