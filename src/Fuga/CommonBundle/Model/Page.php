<?php

namespace Fuga\CommonBundle\Model;

use Fuga\Component\Database\Model;

class Page extends Model {
	
	protected $table = 'page';
	protected $title = 'Разделы';
	protected $module = 'structure';

	protected $order_by = 'sort,name';
	protected $l10n = true;
	protected $activate = true;
	protected $sortable = true;
	protected $treelike = true;
	protected $searchable = true;

	protected $fields = [
		'title' => [
			'name' => 'title',
			'title' => 'Название',
			'type' => 'string',
			'width' => '60%',
			'search' => true
		],
		'name' => [
			'name' => 'name',
			'title' => 'Код (англ.)',
			'type' => 'string',
			'width' => '30%',
			'help' => 'англ. буквы без пробелов',
			'search' => true
		],
		'url' => [ // todo убрать это в Menu
			'name' => 'url',
			'title' => 'Ссылка',
			'type' => 'string'
		],
		'parent_id' => [
			'name' => 'parent_id',
			'title' => 'Находится в',
			'type' => 'select_tree',
			'l_table' => 'page',
			'l_field' => 'title',
			'l_sort' => 'sort,title',
			'l_lang' => true
		],
		'module_id' => [
			'name' => 'module_id',
			'title' => 'Тип',
			'type' => 'select',
			'l_table' => 'module',
			'l_field' => 'title',
			'query' => "id NOT IN(17)"
		],
		'content' => [
			'name' => 'content',
			'title' => 'Текст',
			'type' => 'html'
		],
		'left_key' => [
			'name'  => 'left_key',
			'title' => 'Левый ключ',
			'type'  => 'number',
			'readonly' => true
		],
		'right_key' => [
			'name'  => 'right_key',
			'title' => 'Правый ключ',
			'type'  => 'number',
			'readonly' => true
		],
		'level' => [
			'name'  => 'level',
			'title' => 'Уровень',
			'type'  => 'number',
			'readonly' => true
		]
	];

}