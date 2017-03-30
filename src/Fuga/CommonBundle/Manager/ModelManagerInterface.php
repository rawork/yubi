<?php

namespace Fuga\CommonBundle\Manager;

interface ModelManagerInterface
{
	public function findBy($criteria = '', $sort = null, $limit = null);
}

