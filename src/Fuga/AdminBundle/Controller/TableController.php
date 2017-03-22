<?php

namespace Fuga\AdminBundle\Controller;

class TableController extends AdminController
{
	public function create($state, $module, $entity)
	{
		$table = $this->get('container')->getTable($module.'_'.$entity);
		$this->get('session')->getFlashBag()->add(
			'admin.message',
			$table->create() ? 'Таблица создана' : 'Таблица уже существует'
		);

		return $this->redirect($this->generateUrl(
			'admin_entity_index',
			array('state' => $state, 'module' => $module, 'entity' => $entity)
		));
	}

	public function alter($state, $module, $entity)
	{
		$table = $this->get('container')->getTable($module.'_'.$entity);
		$this->get('session')->getFlashBag()->add(
			'admin.message',
			$table->alter() ? 'Структура таблицы обновлена' : 'Ошибка обновления структуры таблицы'
		);

		return $this->redirect($this->generateUrl(
			'admin_entity_index',
			array('state' => $state, 'module' => $module, 'entity' => $entity)
		));
	}
} 