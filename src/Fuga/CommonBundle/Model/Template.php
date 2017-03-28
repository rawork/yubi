<?php
	
namespace Fuga\CommonBundle\Model;


use Fuga\Component\Database\Model as BaseModel;

class Template extends BaseModel
{
	protected $table = 'template';
	protected $title = 'Шаблоны';
	protected $module = 'template';

	protected $order_by = 'name';
	protected $l10n = true;

	protected $fields = [
		'name' => [
			'name' => 'name',
			'title' => 'Название макета',
			'type' => 'string',
			'width' => '95%'
		],
		'template' => [
			'name' => 'template',
			'title' => 'Ссылка на шаблон',
			'type' => 'string'
		]
	];
}