<?php

namespace Fuga\CommonBundle\Model;


use Fuga\Component\Database\Model as BaseModel;

class SubscribeRubric extends BaseModel
{
	protected $table  = 'subscribe_rubric';
	protected $title  = 'Тематики';
	protected $module = 'subscribe';

	protected $order_by = 'name';

	protected $fields = [
		'name' => [
			'name'  => 'name',
			'title' => 'Имя',
			'type'  => 'string',
			'width' => '95%',
			'search'=> true
		]
	];
}
