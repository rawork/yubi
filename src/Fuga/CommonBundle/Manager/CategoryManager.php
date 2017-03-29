<?php

namespace Fuga\CommonBundle\Manager;

class CategoryManager extends ModelManager {
	
	public function getPathNodes($id = 0){
		$nodes = array();
		if ($this->get('router')->getParam('action') == 'product') {
			$node = $this->getTable('catalog_product')->getItem($id);
			if ($node) {
				$nodes = $this->getTable('catalog_category')->getPrev($node['category_id']);
				$nodes[] = $node; 
			}
		} else {
			$nodes = $this->getTable('catalog_category')->getPrev($id);
		}
		foreach ($nodes as &$node) {
			$node['title'] = $node['name'];
			$node['ref'] = $this->get('router')->generateUrl($this->get('router')->getParam('node'), 'index', array($node['id']));
		}
		
		return $nodes;
	}
	
}