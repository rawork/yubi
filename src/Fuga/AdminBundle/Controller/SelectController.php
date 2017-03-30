<?php

namespace Fuga\AdminBundle\Controller;

use Fuga\CommonBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class SelectController extends Controller
{
	public function complete()
	{
		$results = [];
		$response = new JsonResponse();
		$response->setData($results);

		return $response;
	}
} 