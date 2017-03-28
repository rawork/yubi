<?php

namespace Fuga\CommonBundle\Model;

use Fuga\Component\Database\Model as BaseModel;

class SiteVersion extends BaseModel
{
	protected $table = 'site_version';
	protected $title = 'Версии сайта';
	protected $module = 'config';

	protected $activate = true;

	protected $fields = [
		'title' => [
			'name' => 'title',
			'title' => 'Название',
			'type' => 'string',
			'width' => '40%',
		],
		'folder' => [
			'name' => 'folder',
			'title' => 'Папка',
			'type' => 'string',
			'width' => '40%',
		],
		'language' => [
			'name'  => 'language',
			'title' => 'Язык',
			'width' => '15%',
			'type'  => 'enum',
			'select_values' => '["ru","en"]',
			'defvalue' => 'ru',
		]
	];
}
