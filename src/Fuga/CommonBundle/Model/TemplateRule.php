<?php
	
namespace Fuga\CommonBundle\Model;


use Fuga\Component\Database\Model;

class TemplateRule extends Model
{
	protected $table = 'template_rule';
	protected $title = 'Правила шаблонов';
	protected $module = 'template';

	protected $order_by = 'sort';
	protected $l10n 	= true;
	protected $sortable = true;

	protected $fields = [
		'template_id' => array (
			'name' => 'template_id',
			'title' => 'Шаблон',
			'type' => 'select',
			'l_table' => 'template',
			'l_field' => 'name',
			'l_lang' => true,
			'width' => '31%'
		),
		'type' => array (
			'name' => 'type',
			'title' => 'Тип условия',
			'type' => 'enum',
			'select_values' => '{"F":"Раздел","U":"Параметр URL","T":"Период времени"}',
			'width' => '20%'
		),
		'cond' => array (
			'name' => 'cond',
			'title' => 'Условие',
			'type' => 'string',
			'width' => '20%'
		),
		'datefrom' => array (
			'name' => 'datefrom',
			'title' => 'Начало показа',
			'type' => 'datetime',
			'width' => '12%'
		),
		'datetill' => array (
			'name' => 'datetill',
			'title' => 'Конец показа',
			'type' => 'datetime',
			'width' => '12%'
		)
	];
}