<?php

namespace Fuga\PublicBundle\Controller;

use Fuga\CommonBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CommonController extends Controller {

	public function dinamic($node, $action, $params = [])
	{
		$node = $this->getManager('Fuga:Common:Page')->getNodeByName($node);

		if ($node['module_id']) {
			try {
				return $this->container->callAction(
					$node['module_id_value']['item']['path'].':'.$action,
					$params
				);
			} catch (NotFoundHttpException $e) {
				$this->err($e->getMessage());
				$this->err($e->getTraceAsString());
				throw $this->createNotFoundException($e->getMessage());
			} catch (\Exception $e) {
				$this->err($e->getMessage());
				$this->err($e->getTraceAsString());
				throw new \Exception($e->getMessage());
			}
		}

		return '';
	}

	public function index($node = null, $action = null, $options = [])
	{
		$node = $node ?: '/';
		$action = $action ?: 'index';

		if ($this->get('request')->isXmlHttpRequest()) {
			var_dump('ajax');
			$response = new JsonResponse();
			$response->setData($this->dinamic($node, $action, $options));

			return $response;
		}

		$staticContent = null;
		$nodeItem = $this->getManager('Fuga:Common:Page')->getNodeByName($node);
		if (!$nodeItem) {
			throw $this->createNotFoundException('Несуществующая страница');
		}

		if ($action == 'index') {
			$staticContent = $this->render('page/static', ['node' => $nodeItem]);
		}

		$links = $this->getManager('Fuga:Common:Page')->getNodes('/', true);

		foreach ($links as &$link) {
			$link['children'] = $this->getManager('Fuga:Common:Page')->getNodes($link['name'], true);
		}
		unset($link);

		$params = [
			'h1'        => $nodeItem['title'],
			'title'     => $this->getManager('Fuga:Common:Meta')->getTitle() ?: strip_tags($nodeItem['title']),
			'meta'      => $this->getManager('Fuga:Common:Meta')->getMeta(),
			'links'     => $links,
			'action'    => $action,
			'options'   => $options,
			'curnode'   => $nodeItem,
			'curuser'   => $this->get('security')->getCurrentUserPublic(),
			'locale'    => $this->get('session')->get('locale'),
		];
		$this->get('templating')->assign($params);


		$res = $this->dinamic($node, $action, $options);
		if (is_object($res) && $res instanceof Response) {
			return $res;
		} elseif (is_array($res)) {
			$response = new JsonResponse();
			$response->setData($res);
			return $response;
		}

		$this->get('templating')->assign(['maincontent' => $staticContent.$res]);

		$response = new Response();
		$response->setContent($this->render(
			$this->getManager('Fuga:Common:Template')->getByNode($nodeItem['name']),
			$this->getManager('Fuga:Common:Template')->getVars()
		));

		return $response;
	}
	
	public function block($name)
	{
		$item = $this->getTable('page_block')->getItem("name='{$name}' AND publish=1");
		
		return $item ? $item['content'] : '';
	}
	
	public function breadcrumb($nodes)
	{
		return $this->render('common/breadcrumb', compact('nodes'));
	}
	
	private function getMapList($uri = 0)
	{
		$nodes = [];
		$items = $this->getManager('Fuga:Common:Page')->getNodes($uri);
		$block = strval($uri) == '0' ? '' :  '_sub';
		if (count($items)) {
			foreach ($items as $node) {
				$node['sub'] = '';
				if ($node['module_id']) {
					$controller = $this->container->createController($node['module_id_path']);
					$node['sub'] = $controller->map();
				}
				$node['sub'] .= $this->getMapList($node['id']);
				$nodes[] = $node;
			}
		}
		return $this->render('common/map', compact('nodes', 'block'));
	}

	public function map()
	{
		return $this->getMapList();
	}
	
	public function form($name, Request $request)
	{
		$form = $this->getManager('Fuga:Common:Form')->getForm($name);
		$form->handleRequest();

		if ($form->isSubmitted() && $form->isValid()) {

			$data = [
				'secret' => RECAPTCHA_SECRET_KEY,
				'response' => $this->get('request')->request->get('g-recaptcha-response')
			];

			$verify = curl_init();
			curl_setopt($verify, CURLOPT_URL, RECAPTCHA_URL);
			curl_setopt($verify, CURLOPT_POST, true);
			curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
			curl_setopt($verify, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($verify);

			$json = json_decode($response);

			$params0 = $this->getManager('Fuga:Common:Param')->findAll('form');
			$params = [];
			foreach ($params0 as $param) {
				$params[$param['name']] = $param['type'] == 'int' ? intval($param['value']) : $param['value'];
			}

			$values = $form->getData();
			if ($json->success) {
				$this->get('session')->remove('form_'.$name);
				$this->getManager('Fuga:Common:Form')->send($name, $values);

				$this->get('session')->set(
					'success',
					$this->getManager('Fuga:Common:Param')->getValue('form', 'success')
				);
			} else {
				$this->get('session')->set('form_'.$name, $values);
				$this->get('session')->set(
					'error',
					$this->getManager('Fuga:Common:Param')->getValue('form','securecode_error')
				);
			}

			return $this->reload();
		}

		$message = $this->flash('error');
		if (!$message) {
			$message = $this->flash('success');
		}

		if ($this->get('session')->has('form_'.$name)) {
			$form->setData($this->get('session')->get('form_'.$name));
		}

		return $this->getManager('Fuga:Common:Form')->getTwig()->render(
			'form/new',
			[
				'form' => $form->createView(),
				'recaptcha_key' => RECAPTCHA_PUBLIC_KEY,
				'message' => $message
			]
		);
	}

	// TODO fileupload -> filestorage
	public function fileupload()
	{
		$error = [];
		$msg = [];
		$fileElementName = 'fileToUpload';
		$date = new \Datetime();
		$upload_ref = UPLOAD_REF.$date->format('/Y/m/d/');
		@mkdir(PRJ_DIR.$upload_ref, 0755, true);
		$upload_path = PRJ_DIR.$upload_ref;
		$files_count = isset($_FILES[$fileElementName]["name"]) ? count($_FILES[$fileElementName]["name"]) : 0;
		if (!isset($_FILES[$fileElementName]["name"])) {
			echo 'Не выбраны файлы';
			exit;
		}
		foreach ($_FILES[$fileElementName]["name"] as $i => $file) {
			if(!empty($_FILES[$fileElementName]['error'][$i])) {
				switch($_FILES[$fileElementName]['error'][$i]) {
					case '1':
						$error[] = 'Размер загруженного файла превышает размер установленный параметром upload_max_filesize  в php.ini ';
						break;
					case '2':
						$error[] = 'Размер загруженного файла превышает размер установленный параметром MAX_FILE_SIZE в HTML форме. ';
						break;
					case '3':
						$error[] = 'Загружена только часть файла ';
						break;
					case '4':
						$error[] = 'Файл не был загружен (Пользователь в форме указал неверный путь к файлу). ';
						break;
					case '6':
						$error[] = 'Неверная временная дирректория';
						break;
					case '7':
						$error[] = 'Ошибка записи файла на диск';
						break;
					case '8':
						$error[] = 'Загрузка файла прервана';
						break;
					case '999':
					default:
						$error[] = 'Неизвестный код ошибки';
				}
			} elseif(empty($_FILES[$fileElementName]['tmp_name'][$i]) || $_FILES[$fileElementName]['tmp_name'][$i] == 'none') {
				$error[] = 'Файл не загружен...';
			} else {
				$msg[] = " " . $_FILES[$fileElementName]['name'][$i];
				$file  = $this->get('util')->getNextFileName($upload_ref.$_FILES[$fileElementName]['name'][$i]);
				move_uploaded_file($_FILES[$fileElementName]['tmp_name'][$i], PRJ_DIR.$file);
				$name = $_FILES[$fileElementName]['name'][$i];
				$filesize = @filesize($upload_path.$_FILES[$fileElementName]['name'][$i]);
				$mimetype = $_FILES[$fileElementName]['type'][$i];
				$width = 0;
				$height = 0;
				if ($fileInfo = @GetImageSize(PRJ_DIR.$file)) {
					$width = $fileInfo[0];
					$height = $fileInfo[1];
				}
				$this->get('connection')->insert('system_files', [
					'name' => $name,
					'mimetype' => $mimetype,
					'file' => $file,
					'width' => $width,
					'height' => $height,
					'filesize' => $filesize,
					'table_name' => $this->get('request')->request->get('table_name'),
					'entity_id' => $this->get('request')->request->get('entity_id', true, 0),
					'created' => date('Y-m-d H:i:s')
				]);
				$sizes = $this->get('request')->request->get('sizes');
				if ($sizes) {
					$this->get('imagestorage')->afterSave($file, ['sizes' => $sizes]);
				}
			}
		}

		$response = new Response();
		$response->setContent(count($error) ? implode('<br>', $error) : "Добавлены файлы: ".implode(', ', $msg));

		return $response;
	}
	
}