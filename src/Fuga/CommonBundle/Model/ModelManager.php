<?php

namespace Fuga\CommonBundle\Model;

class ModelManager implements ModelManagerInterface
{
	protected $entityTable;
	protected $connection;
	
	public function findBy($criteria = '', $sort = null, $limit = null)
	{
		$this->getTable($this->entityTable)->getItems($criteria, $sort, $limit);
	}
	
	public function get($name = null)
	{
		global $container;

		if (!$name || 'container' == $name) {
			return $container;
		}

		return $container->get($name);
	}

	/*
	 * return Fuga\Component\Db\Table
	 */
	public function getTable($name)
	{
		return $this->get('container')->getManager('Fuga:Common:Table')->getByName($name);
	}
}