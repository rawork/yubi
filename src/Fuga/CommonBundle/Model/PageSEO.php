<?php

namespace Fuga\CommonBundle\Model;

use Fuga\Component\Database\Model as BaseModel;

class PageSEO extends BaseModel
{
	protected $table = 'page_seo';
	protected $title = 'SEO';
	protected $module = 'structure';

	protected $fields =  [
		'words' => [
			'name' => 'words',
			'title' => 'Строки URI',
			'type' => 'text',
			'help' => 'Через запятую',
			'width' => '20%'
		],
		'keywords' => [
			'name' => 'keywords',
			'title' => 'Подстроки URI',
			'type' => 'text',
			'help' => 'Через запятую',
			'width' => '20%'
		],
		'title' => [
			'name' => 'title',
			'title' => 'Тайтл',
			'type' => 'text',
			'width' => '25%',
			'search' => true
		],
		'meta' => [
			'name' => 'meta',
			'title' => 'Метатеги',
			'type' => 'text',
			'width' => '25%',
			'help' => 'Включая служебные символы',
			'search' => true
		]
	];

}