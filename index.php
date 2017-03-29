<?php

require_once(__DIR__ . '/app/init.php');

$kernel = new Fuga\CommonBundle\Controller\AppController();
$kernel->setContainer($container);
$response = $kernel->handle();

if (!is_object($response) || !($response instanceof \Symfony\Component\HttpFoundation\Response)){
	$container->get('log')->addError('link'.$_SERVER['REQUEST_URI']);
	$container->get('log')->addError('response'.serialize($response));
}

$response->send();
