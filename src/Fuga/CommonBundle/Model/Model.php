<?php
	
namespace Fuga\CommonBundle\Model;

use Fuga\Component\Database\Model as BaseModel;

class Model extends BaseModel
{
	protected $table 	= 'model';
	protected $title	= 'Типы данных';
	protected $module	= 'model';

	protected $order_by	= 'module_id,sort,name';
	protected $sortable	= true;
	protected $activate	= true;

	protected $fields	= [
		'title' => [
			'name'	=> 'title',
			'title' => 'Заголовок',
			'type'	=> 'string',
			'width' => '20%',
			'search'=> true
		],
		'name' => [
			'name'	=> 'name',
			'title' => 'Сист. имя',
			'type'	=> 'string',
			'width' => '20%',
			'help'	=> 'Англ. без пробелов',
			'search' => true
		],
		'module_id' => [
			'name'	=> 'module_id',
			'title' => 'Компонент',
			'type'	=> 'select',
			'help'	=> 'Модуль таблицы',
			'l_table' => 'module',
			'l_field' => 'title',
			'width' => '25%',
		],
		'order_by' => [
			'name' => 'order_by',
			'title' => 'Сортировка',
			'type' => 'string'
		],
		'treelike'	=> [
			'name'	=> 'treelike',
			'title' => 'Древовидный',
			'type'	=> 'checkbox',
			'width' => '1%',
			'group_update' => true
		],
		'l10n'	=> [
			'name'	=> 'l10n',
			'title' => 'Мультияз.',
			'type'	=> 'checkbox',
			'width' => '1%',
			'group_update' => true
		],
		'sortable'	=> [
			'name'	=> 'sortable',
			'title'	=> 'Поле сорт.',
			'type'	=> 'checkbox',
			'width' => '1%',
			'group_update' => true
		],
		'activate' => [
			'name'	=> 'activate',
			'title'	=> 'Поле акт.',
			'type'	=> 'checkbox',
			'width' => '1%',
			'group_update' => true
		],
		'searchable' => [
			'name'	=> 'searchable',
			'title' => 'Поиск',
			'type'	=> 'checkbox',
			'width' => '1%',
			'group_update' => true
		]
	];
}