<?php

namespace Fuga\AdminBundle\Controller;

use Fuga\CommonBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class GalleryController extends Controller
{
	public function delete()
	{
		$id = $this->get('request')->request->getInt('id', 0);
		$sql = "SELECT * FROM system_files WHERE id= :id ";
		$stmt = $this->get('connection')->prepare($sql);
		$stmt->bindValue('id', $id);
		$stmt->execute();
		$file = $stmt->fetch();
		$response = new JsonResponse();

		if ($file) {
			$field = $this->getTable($file['table_name'])->fields[$file['field_name']];
			$params = json_decode($field['params'], true);
			if (json_last_error() == JSON_ERROR_NONE && array_key_exists('sizes', $params)) {
				$sizes = $params['sizes'];
				$sizes['default'] = ['width' => 50, 'height' => 50, 'adaptive' => true];
				$this->get('imagestorage')->setOptions(array('sizes' => $sizes));
			}

			$this->get('imagestorage')->remove($file['file']);
			$this->get('connection')->delete('system_files', array('id' => $id));
			$response->setData(array('ok' => true));
		} else {
			$response->setData(array('error' => 'Ошибка удаления файла'));
		}

		return $response;
	}
}