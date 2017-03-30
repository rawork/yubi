<?php

namespace Fuga\CommonBundle\Manager;

use Fuga\Component\Container;
use Doctrine\DBAL\Connection;
use Fuga\Component\Database\Table;

class ModelManager implements ModelManagerInterface
{
	/**
	 * @var string
	 */
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
	
	/**
	 * @return Table
	 */
	public function getTable($name)
	{
		return $this->container->getManager('Fuga:Common:Table')->getByName($name);
	}
}