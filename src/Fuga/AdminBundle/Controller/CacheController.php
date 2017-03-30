<?php

namespace Fuga\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class CacheController extends AdminController
{
	public function clear()
	{
		$this->get('cache')->deleteAll();

		$response = new JsonResponse();
		$response->setData(['content' => 'Кэш очищен']);
		$response->prepare($this->get('request'));

		return $response;
	}
} 