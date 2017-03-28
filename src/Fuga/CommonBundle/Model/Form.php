<?php

namespace Fuga\CommonBundle\Model;


use Fuga\Component\Database\Model as BaseModel;

class Form extends BaseModel {
	
	protected $table = 'form';
	protected $title = 'Веб формы';
	protected $module = 'form';

	protected $order_by = 'name';
	protected $activate = true;
	protected $l10n = true;

	protected $fields = [
		'title' => array (
			'name' => 'title',
			'title' => 'Название',
			'type' => 'string',
			'width' => '22%',
			'search' => true
		),
		'name' => array (
			'name' => 'name',
			'title' => 'Идентификатор',
			'type' => 'string',
			'width' => '22%',
		),
		'email' => array (
			'name' => 'email',
			'title' => 'E-mail получателя',
			'type' => 'text',
			'width' => '22%',
		),
		'submit_text' => array (
			'name' => 'submit_text',
			'title' => 'Submit кнопка',
			'type' => 'string',
			'width' => '22%'
		),
		'is_defense' => array (
			'name' => 'is_defense',
			'title' => 'Captcha',
			'type' => 'checkbox',
			'width' => '1%',
			'group_update' => true
		)
	];


}
