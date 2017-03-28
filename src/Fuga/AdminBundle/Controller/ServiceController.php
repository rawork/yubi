<?php

namespace Fuga\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ServiceController extends AdminController
{
	public function index()
	{
		$state = 'system';
		$module = 'config';
		$message = null;
		if ($archiveReport = $this->get('session')->getFlashBag()->get('archive.report')) {
			$message = array_shift($archiveReport);
		}

		$finder = new Finder();
		$finder->files()->in(BACKUP_DIR.'/')->name('*.gz');

		$response = new Response();
		$response->setContent($this->render('@Admin/service/index', compact('finder', 'message', 'state', 'module')));
		$response->prepare($this->get('request'));

		return $response;
	}

	public function restore() {
		$filepath = PRJ_DIR . '/app/restore.php';
		$filename = 'restore.php';

		if (!$this->get('fs')->exists($filepath)) {
			throw $this->createNotFoundException('File not found');
		}

		$response = new BinaryFileResponse($filepath);
		$response->setContentDisposition(
			ResponseHeaderBag::DISPOSITION_ATTACHMENT,
			$filename
		);
		$response->prepare($this->get('request'));

		return $response;
	}
} 