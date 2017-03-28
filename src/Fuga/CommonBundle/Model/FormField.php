<?php

namespace Fuga\CommonBundle\Model;

use Fuga\Component\Database\Model as BaseModel;

class FormField extends BaseModel {
	
	protected $table = 'form_field';
	protected $title = 'Поля формы';
	protected $module = 'form';

	protected $order_by = 'name';
	protected $activate = true;
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
			'select_values' => '{"string":"Строка","text":"Текст","select":"Список","checkbox":"Флаг","file":"Файл","password":"Пароль"}',
			'width' => '15%'
		],
		'select_table' => [
			'name' => 'select_table',
			'title' => 'Таблица значений',
			'type' => 'string',
			'help' => 'Таблица значений'
		],
		'select_name' => [
			'name' => 'select_name',
			'title' => 'Поле залоговка',
			'type' => 'string',
		],
		'select_value' => [
			'name' => 'select_value',
			'title' => 'Поле значения',
			'type' => 'string',
		],
		'select_filter' => [
			'name' => 'select_filter',
			'title' => 'Запрос',
			'type' => 'string',
		],
		'select_values' => [
			'name' => 'select_values',
			'title' => 'Значения',
			'type' => 'string'
		],
		'is_required' => [
			'name' => 'is_required',
			'title' => 'Обяз.',
			'type' => 'checkbox',
			'group_update'  => true,
			'width' => '1%'
		],
		'is_check' => [
			'name' => 'is_check',
			'title' => 'Пров.',
			'type' => 'checkbox',
			'group_update'  => true,
			'width' => '1%'
		]
	];
}
