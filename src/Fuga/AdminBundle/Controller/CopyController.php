<?php

namespace Fuga\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class CopyController extends AdminController
{
	public function copy($state, $module, $entity, $id, $quantity)
	{
		set_time_limit(0);
		$this->get('session')->getFlashBag()->add(
			'admin.message',
			$this->getTable($entity)->copy($id, $quantity) ? 'Скопировано' : 'Ошибка копирования'
		);

		return $this->redirect($this->generateUrl(
			'admin_entity_index',
			array('state' => $state, 'module' => $module, 'entity' => $entity)
		));
	}

	public function dialog($id)
	{
		$response = new JsonResponse();
		$response->setData(array(
			'title' => 'Копирование элемента',
			'button' => '<a class="btn btn-default" data-dismiss="modal" aria-hidden="true">Закрыть</a><a class="btn btn-success btn-copy" data-id="'.$id.'">Копировать</a>',
			'content' => '
<div class="control-group" id="copy-input">
  <label class="control-label" for="inputError">Количество новых (1-10)</label>
  <div class="controls">
    <input type="text" name="amount" id="copy-amount" value="1">
    <span class="help-inline" id="copy-help"></span>
  </div>
</div>'
		));

		return $response;
	}

} 