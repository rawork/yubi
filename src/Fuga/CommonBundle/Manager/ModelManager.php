<?php

namespace Fuga\CommonBundle\Manager;

use Fuga\Component\Container;
use Doctrine\DBAL\Connection;

class ModelManager implements ModelManagerInterface
{
	protected $entityTable;
	/**
	 * @var Connection;
	 */
	protected $connection;
	/**
	 * @var Container|null
	 */
	protected $container;
	
	public function findBy($criteria = '', $sort = null, $limit = null)
	{
		$this->getTable($this->entityTable)->getItems($criteria, $sort, $limit);
	}

	public function setContainer(\Fuga\Component\Container &$container)
	{
		$this->container = $container;
	}
	
	public function get($name = null)
	{
		if (!$name || 'container' == $name) {
			return $this->container;
		}

		return $this->container->get($name);
	}

	/*
	 * @return Fuga\Component\Database\Table
	 */
	public function getTable($name)
	{
		return $this->container->getManager('Fuga:Common:Table')->getByName($name);
	}
}