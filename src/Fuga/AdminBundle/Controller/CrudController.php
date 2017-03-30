<?php

namespace Fuga\AdminBundle\Controller;

use Fuga\AdminBundle\Action\IndexAction;
use Symfony\Component\HttpFoundation\Response;

class CrudController extends AdminController
{
	public function index($state, $module, $entity)
	{
		$action = new IndexAction($state, $module, $entity);
		$action->setContainer($this->container);

		$res = $action->run();

		if (is_object($res) && $res instanceof Response) {
			return $res;
		}

		$response = new Response();
		$response->setContent($res);

		return $response;
	}

	public function add($state, $module, $entity)
	{
		$table = $this->getTable($entity);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$lastId = $table->insertGlobals();
			$this->get('session')->getFlashBag()->add(
				'admin.message',
				$lastId ? 'Добавлено' : 'Ошибка добавления'
			);
			if ($lastId) {
				if ($this->get('request')->request->get('utype', 0) == 1) {
					return $this->redirect($this->generateUrl(
						'admin_entity_edit',
						['state' => $state, 'module' => $module, 'entity' => $entity, 'id' => $lastId]
					));
				} else {
					return $this->redirect($this->generateUrl(
						'admin_entity_index',
						['state' => $state, 'module' => $module, 'entity' => $entity]
					));
				}
			} else {
				return $this->redirect($this->generateUrl(
					'admin_entity_add',
					['state' => $state, 'module' => $module, 'entity' => $entity]
				));
			}
		}

		$links = [
			[
				'ref' => $this->generateUrl(
					'admin_entity_index',
					['state' => $state, 'module' => $module, 'entity' => $entity]
				),
				'name' => 'Список элементов',
			]
		];

		$message = null;
		if ($adminMessage = $this->get('session')->getFlashBag()->get('admin.message')) {
			$message = array_shift($adminMessage);
		}

		$params = [
			'links' => $links,
			'state' => $state,
			'module' => $module,
			'entity' => $entity,
			'table' => $table,
			'message' => $message,
			'title' => $table->title,
			'isRoot' => $this->get('security')->isSuperuser(),
		];

		return new Response($this->render('@Admin/action/add', $params));
	}

	public function edit($state, $module, $entity, $id)
	{
		$table = $this->getTable($entity);

		if ('POST' == $_SERVER['REQUEST_METHOD']) {
			$this->get('session')->getFlashBag()->add(
				'admin.message',
				$table->updateGlobals() ? 'Обновлено' : 'Ошибка обновления'
			);

			if ($this->get('request')->request->get('utype', 0) == 1) {
				return $this->redirect($this->generateUrl(
					'admin_entity_edit',
					['state' => $state, 'module' => $module, 'entity' => $entity, 'id' => $id]
				));
			} else {
				return $this->redirect($this->generateUrl(
					'admin_entity_index',
					['state' => $state, 'module' => $module, 'entity' => $entity]
				));
			}
		}

		$links = [
			[
				'ref' => $this->generateUrl(
					'admin_entity_index',
					['state' => $state, 'module' => $module, 'entity' => $entity]
				),
				'name' => 'Список элементов'
			]
		];
		$item = $table->getItem($id);

		if (!$item) {
			return $this->redirect($this->generateUrl(
				'admin_entity_index',
				['state' => $state, 'module' => $module, 'entity' => $entity]
			));
		}

		$message = null;
		if ($adminMessage = $this->get('session')->getFlashBag()->get('admin.message')) {
			$message = array_shift($adminMessage);
		}

		$params = [
			'state' => $state,
			'module' => $module,
			'entity' => $entity,
			'item' => $item,
			'title' => $table->title,
			'message' => $message,
			'isRoot' => $this->get('security')->isSuperuser(),
			'table' => $table,
			'links' => $links,
		];

		return new Response($this->render('@Admin/action/edit', $params));
	}

	public function delete($state, $module, $entity, $id)
	{
		$id = 'id='.$id;
		$table = $this->getTable($entity);
		$this->get('session')->getFlashBag()->add(
			'admin.message',
			$this->getManager('Fuga:Common:Table')->deleteItem($table->getName(), $id) ? 'Удалено' : 'Ошибка удаления'
		);

		return $this->redirect($this->generateUrl(
			'admin_entity_index',
			['state' => $state, 'module' => $module, 'entity' => $entity]
		));
	}

	public function groupedit($state, $module, $entity)
	{
		$ids = $this->get('request')->request->get('ids');
		$table = $this->getTable($entity);

		if (!$ids || $this->get('request')->request->getInt('edited', 0) == 1) {
			$this->get('session')->getFlashBag()->add(
				'admin.message',
				$table->group_update() ? 'Обновлено' : 'Ошибка обновления записей'
			);

			return $this->redirect($this->generateUrl(
				'admin_entity_index',
				['state' => $state, 'module' => $module, 'entity' => $entity]
			));
		}

		$table->select(
			[
				'where' => 'id IN('.$ids.')',
			]
		);
		$items = $table->getNextArrays(false);
		if (count($items) == 0) {
			return $this->redirect($this->generateUrl(
				'admin_entity_index',
				['state' => $state, 'module' => $module, 'entity' => $entity]
			));
		}

		$links = [
			[
				'ref' => $this->generateUrl(
						'admin_entity_index',
						['state' => $state, 'module' => $module, 'entity' => $entity]
					),
				'name' => 'Список элементов'
			]
		];

		$message = null;
		if ($adminMessage = $this->get('session')->getFlashBag()->get('admin.message')) {
			$message = array_shift($adminMessage);
		}

		$params = [
			'state' => $state,
			'module' => $module,
			'entity' => $entity,
			'items' => $items,
			'title' => $table->title,
			'message' => $message,
			'isRoot' => $this->get('security')->isSuperuser(),
			'table' => $table,
			'links' => $links,
			'ids' => $ids,
		];

		return new Response($this->render('@Admin/action/groupedit', $params));
	}

	public function groupdelete($state, $module, $entity)
	{
		$ids = explode(',', $this->get('request')->request->get('ids'));
		if(is_array($ids)) {
			$query = 'id IN('.implode(',', $ids).') ';
			$isDeleted = $this->getManager('Fuga:Common:Table')->deleteItem($entity, $query);
		} else {
			$isDeleted = false;
		}

		$this->get('session')->getFlashBag()->add(
			'admin.message',
			$isDeleted ? 'Удалено '.count($ids).' записей' : 'Ошибка группового удаления'
		);

		return $this->redirect($this->generateUrl(
			'admin_entity_index',
			['state' => $state, 'module' => $module, 'entity' => $entity]
		));
	}

	// TODO export not work
	public function export($state, $module, $entity)
	{
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$ret = $this->uai->module->exportCSV();
			$ret = '<textarea width="90%" cols="50" rows="10" name="data" id="data">'.addslashes($ret).'</textarea>';
			return $ret;
		}

		$ret = '<b>Экспорт CSV</b><br><table border="0" width="70%">
<form enctype="multipart/form-data" action="'.$this->fullRef.'/export" method="post">
<tr bgcolor="#fafafa"><td align="right"><input type="submit" value="Экспорт -&gt;"></td></tr>
</form></table>';

		return $ret;
	}

	// TODO import not work
	function import()
	{
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$this->uai->messageAction($this->uai->module->importCSV() ? 'Импорт выполнен' : 'Ошибки при импорте', $this->uai->getBaseRef().'&action=s_import');
		}

		$ret = '<b>Импорт CSV</b><br><table border="0" width="70%">
<form enctype="multipart/form-data" action="'.$this->fullRef.'/import" method="post">
<tr bgcolor="#fafafa">
	<th align="left" width="20%">CSV-файл <small>(макс '.get_cfg_var('upload_max_filesize').')</small></th>
	<td><input name="csv_file" type="file" style="width:100%"></td></tr>
<tr><td colspan="2" align="right"><input type="submit" value="ИмпортироватьЭ></td></tr></form></table>';

		return $ret;
	}
}