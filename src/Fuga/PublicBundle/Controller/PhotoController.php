<?php

namespace Fuga\PublicBundle\Controller;


use Fuga\CommonBundle\Controller\Controller;

class PhotoController extends Controller
{
	public function index()
	{
		$events = $this->getTable('events')->getItems('publish=1');

		return $this->render('photo/index', compact('events'));
	}

}