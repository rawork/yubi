<?php

namespace Fuga\CommonBundle\Model;


use Fuga\Component\Database\Model as BaseModel;

class PageBlock extends BaseModel
{
	protected $table = 'page_block';
	protected $title = 'Инфоблоки';
	protected $module = 'structure';

	protected $order_by = 'title';
	protected $l10n = true;
	protected $activate = true;

	protected $fields = [
		'title' => [
			'name' => 'title',
			'title' => 'Название',
			'type' => 'string',
			'width' => '45%',
			'search' => true,
		],
		'name' => [
			'name' => 'name',
			'title' => 'Код',
			'type' => 'string',
			'width' => '45%',
			'search'=> true
		],
		'content' => [
			'name'  => 'content',
			'title' => 'Текст',
			'type' => 'html'
		]
	];

}