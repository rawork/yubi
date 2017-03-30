<?php

namespace Fuga\Component\Templating;

use Monolog\Logger;

class TwigTemplating implements TemplatingInterface
{
	/**
	 * @var Logger
	 */
	protected $log;

	/**
	 * @var \Twig_Environment
	 */
	protected $engine;

	public function __construct(\Twig_Environment $engine, Logger $log)
	{
		$this->log = $log;
		$this->engine = $engine;
	}
	
	public function assign($params = [])
	{
		foreach ($params as $paramName => $paramValue) {
			$this->engine->addGlobal($paramName, $paramValue);
		}
	}
	
	public function render($template, $params = [], $silent = false)
	{
		try {
			if (!preg_match('/\.html\.twig$/', $template) && !preg_match('/\.json\.twig$/', $template)) {
				$template .= '.html.twig';
			}

			if (empty($template)) {
				throw new \Exception('Для обработки передан шаблон без названия.');
			}

			return $this->engine->render($template, $params);
		} catch (\Exception $e) {
			$this->log->addError($e->getMessage());
			$this->log->addError($e->getTraceAsString());
			if (!$silent) {
				if (PRJ_ENV == 'dev') {
					throw $e;
				} else {
					throw new \Exception('Ошибка рендеринга шаблона "'.$template.'".');
				}
			}
		}

		return false;
	}

	public function getEngine()
	{
		return $this->engine;
	}
	
}
