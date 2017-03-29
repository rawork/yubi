<?php

define('LIB_VERSION', '7.6.0');
define('LIB_DATE', '2017.03.22');

mb_http_input('UTF-8'); 
mb_http_output('UTF-8'); 
mb_internal_encoding("UTF-8");

$loader = require __DIR__.'/../vendor/autoload.php';


function exception_handler($exception) 
{	
	$statusCode = $exception instanceof \Fuga\Component\Exception\NotFoundHttpException 
			? $exception->getStatusCode() 
			: 500;
	$message = $statusCode != 500 || PRJ_ENV == 'prod'? 'Произошла внутренняя ошибка сервера. Обратитесь к администратору' : $exception->getMessage();

	if (isset($_SERVER['REQUEST_URI'])) {
		$controller = new Fuga\CommonBundle\Controller\ExceptionController();
		$res = $controller->index($statusCode, $message);
		$res->send();
	} else {
		echo $message;
	}
}

if (! function_exists('array_column')) {
	function array_column(array $input, $columnKey, $indexKey = null) {
		$array = array();
		foreach ($input as $value) {
			if ( ! isset($value[$columnKey])) {
				trigger_error("Key \"$columnKey\" does not exist in array");
				return false;
			}
			if (is_null($indexKey)) {
				$array[] = $value[$columnKey];
			}
			else {
				if ( ! isset($value[$indexKey])) {
					trigger_error("Key \"$indexKey\" does not exist in array");
					return false;
				}
				if ( ! is_scalar($value[$indexKey])) {
					trigger_error("Key \"$indexKey\" does not contain scalar value");
					return false;
				}
				$array[$value[$indexKey]] = $value[$columnKey];
			}
		}
		return $array;
	}
}

//set_exception_handler('exception_handler');

if (file_exists(__DIR__.'/config/config.php')) {
	require_once __DIR__.'/config/config.php';
}

$container = new Fuga\Component\Container($loader);

// инициализация переменных
if ('cli' != php_sapi_name()) {
	$container->initialize();
}