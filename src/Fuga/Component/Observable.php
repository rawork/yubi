<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 28/02/16
 * Time: 11:23
 */

namespace Fuga\Component;


trait Observable
{
	private $storage;

	function __construct()
	{
		$this->storage = new \SplObjectStorage();
	}

	function attach(\SplObserver $observer)
	{
		$this->storage->attach($observer);
	}

	function detach(\SplObserver $observer)
	{
		$this->storage->detach($observer);
	}

	function notify()
	{
		foreach($this->storage as $obj)
		{
			$obj->update($this);
		}
	}
	//...
}
