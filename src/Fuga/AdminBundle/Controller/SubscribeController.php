<?php

namespace Fuga\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class SubscribeController extends AdminController
{
	public function export()
	{
		$state = 'service';
		$module = 'subscribe';
		$rubrics = $this->getTable('subscribe_rubric')->getItems();
		$last_update = $this->getManager('Fuga:Common:Param')->getValue('subscribe', 'last_update');
		if ($last_update == '0000-00-00 00:00:00') {
			$last_update = 'Никогда';
		} else {
			$date = new \DateTime($last_update['value']);
			$last_update = $date->format('d.m.Y H:i:s');
		}

		$response = new Response();
		$response->setContent($this->render('admin/subscribe/export', compact('rubrics', 'last_update', 'state', 'module')));
		$response->prepare($this->get('request'));

		return $response;
	}

	public function download(){
		$time = time();
		$filepath = PRJ_DIR . '/app/cache/subscribe.txt';
		$filename = 'subscribe'.date('YmdHis', $time).'.txt';
		$last_update = $this->getManager('Fuga:Common:Param')->getValue('subscribe', 'last_update');
		if ($this->get('request')->query->get('all')) {
			$last_update = '0000-00-00 00:00:00';
		}
		$content = array('Выгрузка подписчиков от '.date('d.m.Y H:i:s', $time));
		$rubrics = $this->getTable('subscribe_rubric')->getItems();
		foreach ($rubrics as $rubric) {
			$content[] = $rubric['name'];
			$sql = 'SELECT t0.email FROM subscribe_subscriber t0 JOIN subscribe_subscriber_rubric t1 ON t0.id=t1.subscriber_id WHERE to.is_active=1 AND t1.rubric_id= :rubric AND t0.date > "'.$last_update.'"';
			$stmt = $this->get('connection')->prepare($sql);
			$stmt->bindValue('rubric', $rubric['id']);
			$stmt->execute();
			while ($email = $stmt->fetch()){
				$content[] = $email['email'];
			}
		}

		$fh = fopen($filepath, 'w');
		fwrite($fh, implode("\n", $content));
		fclose($fh);

		$this->get('connection')->update(
			'config_param',
			array(
				'value' => date('Y-m-d H:i:s', $time)
			),
			array(
				'module' => 'subscribe',
				'name'   => 'last_update',
			)
		);

		$response = new BinaryFileResponse($filepath);
		$response->setContentDisposition(
			ResponseHeaderBag::DISPOSITION_ATTACHMENT,
			$filename
		);
		$response->prepare($this->get('request'));

		return $response;
	}
} 