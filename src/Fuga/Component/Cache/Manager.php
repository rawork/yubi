<?php

namespace Fuga\Component\Cache;

use Fuga\Component\Observer;

class Manager extends Observer
{
	private $_container;
	private $_rules;

	public function __construct($container, $rules)
	{
		$this->_container = $container;
		$this->_rules = $rules;
	}

	public function doUpdate(\SplSubject $observable)
	{
		// todo clear cache
	}
}