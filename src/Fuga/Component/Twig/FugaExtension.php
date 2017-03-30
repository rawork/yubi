<?php

namespace Fuga\Component\Twig;

class FugaExtension  extends \Twig_Extension
{

	public function getFunctions()
	{
		return [
			new \Twig_SimpleFunction('render', [$this, 'render']),
			new \Twig_SimpleFunction('renderJS', [$this, 'renderJS']),
			new \Twig_SimpleFunction('renderCSS', [$this, 'renderCSS']),
			new \Twig_SimpleFunction('path', [$this, 'path']),
			new \Twig_SimpleFunction('t', [$this, 'translate']),
			new \Twig_SimpleFunction('asset', [$this, 'asset']),
		];
	}

	public function getFilters()
	{
		return [
			new \Twig_SimpleFilter('format_date', [$this, 'formatDate']),
			new \Twig_SimpleFilter('file_size', [$this, 'fileSize']),
			new \Twig_SimpleFilter('strpad', [$this, 'strpad']),
		];
	}

	public function render($path, $options = [])
	{
		return $GLOBALS['container']->callAction($path, $options);
	}

	public function renderJS()
	{
		$text = '';
		$files = $GLOBALS['container']->getManager('Fuga:Common:Template')->getJs();
		foreach ($files as $file){
			$text .= '<script src="'.$this->asset($file).'"></script>';
		}

		return $text;
	}

	public function renderCSS()
	{
		$text = '';
		$files = $GLOBALS['container']->getManager('Fuga:Common:Template')->getCss();
		foreach ($files as $file){
			$text .= '<link href="'.$this->asset($file).'" rel="stylesheet" media="screen">';
		}

		return $text;
	}

	public function path($name, $options = [], $locale = PRJ_LOCALE)
	{
		if (isset($options['node']) && '/' == $options['node']) {
			unset($options['node']);
		}
		return ($locale != PRJ_LOCALE ? '/'.$locale : '').$GLOBALS['container']->get('router')->getGenerator()->generate($name, $options);
	}

	public function formatDate($string, $format, $simple = true)
	{
		return $GLOBALS['container']->get('util')->format_date($string, $format, $simple);
	}

	public function strpad($input, $padlength, $padstring = '', $padtype = STR_PAD_LEFT)
	{
		return str_pad($input, $padlength, $padstring, $padtype);
	}

	public function fileSize($string)
	{
		return $GLOBALS['container']->get('util')->getSize($string);
	}

	public function translate($string)
	{
		return $GLOBALS['container']->get('translator_local')->t($string);
	}

	public function asset($path, $cache = true)
	{
		if(file_exists(PRJ_DIR.$path) && $cache){
			return PRJ_REF.$path.'?'.date('YmdHis',filemtime(PRJ_DIR.$path));
		}

		return PRJ_REF.$path;
	}

	public function getName()
	{
		return 'fuga';
	}
} 