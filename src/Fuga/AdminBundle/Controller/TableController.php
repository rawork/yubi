<?php

namespace Fuga\AdminBundle\Controller;

class TableController extends AdminController
{
	public function create($state, $module, $entity)
	{
		$table = $this->getTable($entity);
		$this->get('session')->getFlashBag()->add(
			'admin.message',
			$table->create() ? 'Таблица создана' : 'Таблица уже существует'
		);

		return $this->redirect($this->generateUrl(
			'admin_entity_index',
			['state' => $state, 'module' => $module, 'entity' => $entity]
		));
	}

	public function alter($state, $module, $entity)
	{
		$table = $this->getTable($entity);
		$this->get('session')->getFlashBag()->add(
			'admin.message',
			$table->alter() ? 'Структура таблицы обновлена' : 'Ошибка обновления структуры таблицы'
		);

		return $this->redirect($this->generateUrl(
			'admin_entity_index',
			['state' => $state, 'module' => $module, 'entity' => $entity]
		));
	}

	public function drop($state, $module, $entity)
	{
		$table = $this->getTable($entity);

		$this->get('connection')->delete('model_field', ['table_id' => $table->id]);
		$this->get('connection')->delete('model', ['id' => $table->id]);

		$this->get('session')->getFlashBag()->add(
			'admin.message',
			$table->drop() ? 'Таблица удалена' : 'Ошибка удаления таблицы'
		);

		return $this->redirect($this->generateUrl(
			'admin_entity_index',
			['state' => $state, 'module' => $module, 'entity' => $entity]
		));
	}

	public function truncate($state, $module, $entity)
	{
		$table = $this->getTable($entity);


		$this->get('session')->getFlashBag()->add(
			'admin.message',
			$table->truncate() ? 'Таблица очищена' : 'Ошибка очищения таблицы'
		);

		return $this->redirect($this->generateUrl(
			'admin_entity_index',
			['state' => $state, 'module' => $module, 'entity' => $entity]
		));
	}
} 