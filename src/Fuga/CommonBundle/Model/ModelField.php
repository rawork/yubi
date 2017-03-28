<?php
	
namespace Fuga\CommonBundle\Model;

use Fuga\Component\Database\Model as BaseModel;

class ModelField extends BaseModel
{
	protected $table 	= 'model_field';
	protected $title	= 'Поля';
	protected $module 	= 'model';

	protected $order_by	= 'table_id,sort';
	protected $sortable	= true;
	protected $activate = true;

	protected $fields = [
		'title'		=> [
			'name'  => 'title',
			'title' => 'Заголовок',
			'type'  => 'string',
			'width' => '21%',
			'search'=> true
		],
		'name' => [
			'name'		=> 'name',
			'title'		=> 'Сист. имя',
			'type'		=> 'string',
			'help'		=> 'Англ. название поля',
			'width'		=> '21%',
			'search'	=> true
		],
		'table_id' => [
			'name'		=> 'table_id',
			'title'		=> 'Таблица',
			'type'		=> 'select',
			'l_table'	=> 'model',
			'l_field'	=> 'title',
			'width'		=> '21%',
			'search'	=> true
		],
		'type' => [
			'name'		=> 'type',
			'title'		=> 'Тип поля',
			'type'		=> 'enum',
			'select_values' => '{"html":"HTML","select":"Выбор","select_tree":"Выбор из дерева","select_list":"Выбор (мульти)","color":"Выбор цвета","gallery":"Галерея","date":"Дата","datetime":"Дата и время","currency":"Деньги","image":"Изображение","password":"Пароль","enum":"Перечисление","string":"Строка","structure":"Структура","text":"Текст","file":"Файл","checkbox":"Флажок","number":"Целое число"}',
			'defvalue'	=> 'string',
			'width'		=> '21%'
		],
		'select_values' => [
			'name'  => 'select_values',
			'title' => 'Значения',
			'type'  => 'text',
			'help'  => 'JSON-type'
		],
		'params' => [
			'name'  => 'params',
			'title' => 'Параметры',
			'type'  => 'text'
		],
		'width' => [
			'name'  => 'width',
			'title' => 'Ширина',
			'type'  => 'string',
			'width' => '10%',
			'defvalue' => '95%',
			'group_update' => true
		],
		'group_update' => [
			'name'  => 'group_update',
			'title' => 'G',
			'type'  => 'checkbox',
			'width' => '1%',
			'group_update' => true,
			'help'  => 'Групповое обновление'
		],
		'readonly' => [
			'name'  => 'readonly',
			'title' => 'R',
			'type'  => 'checkbox',
			'width' => '1%',
			'group_update' => true,
			'help' => 'Только чтение'
		],
		'search' => [
			'name'  => 'search',
			'title' => 'S',
			'type'  => 'checkbox',
			'width' => '1%',
			'group_update' => true,
			'help' => 'Поиск'
		],
		'is_required' => [
			'name' => 'is_required',
			'title' => 'Обяз.',
			'type' => 'checkbox',
			'group_update'  => true,
			'width' => '1%'
		],
		'defvalue' => [
			'name'  => 'defvalue',
			'title' => 'Значение по умолчанию',
			'search' => true,
			'type'  => 'string'
		]
	];
}