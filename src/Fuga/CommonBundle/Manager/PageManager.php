<?php

namespace Fuga\CommonBundle\Manager;

class PageManager extends ModelManager {
	
	public function getNodes($uri = null, $recursive = false, $where = "publish=1") {
		$sql = 'SELECT t1.*, t3.name as module_id_name, t3.path as module_id_path 
			FROM page as t1 
			LEFT JOIN page as t2 ON t1.parent_id = t2.id 
			LEFT JOIN module as t3 ON t1.module_id = t3.id 
			WHERE t1.publish = 1 AND t1.locale = :locale 
			AND '.(is_numeric($uri) ? ($uri === null ? ' t1.parent_id=0 ' : 't2.id='.$uri.' ') : "t2.name='".$uri."' ").
			'ORDER BY t1.sort, t1.name';
		$stmt = $this->container->get('connection')->prepare($sql);
		$stmt->bindValue('locale', $this->get('session')->get('locale'));
		$stmt->execute();
		$nodes = [];
		while ($row =  $stmt->fetch()) {
			if (array_key_exists('id', $row)) {
				$nodes[$row['id']] = $row;
			} else {
				$nodes[] = $row;
			}
		}

		foreach ($nodes as &$node) {
			if ($recursive) {
				$node['children'] = $this->getNodes($node['name'], $recursive, $where);
			}
			$node['class'] = empty($node['children']) ? 'collapsed' : 'leaf';
			$node['ref'] = $this->getUrl($node);
		}
		
		return $nodes;	
	}
	
	public function getUrl($node) {
		return trim($node['url']) ?: ($this->get('session')->get('locale') != 'ru' ? '/'.$this->get('session')->get('locale') : '').$this->get('router')->getGenerator()->generate('public_page', array('node' => $node['name']));
	}
	
	public function getPathNodes($id = 0) {
		$titles = array('ru' => 'Главная', 'en' => 'Home');
		$nodes = $this->getTable('page')->getPrev($id);
		if ($nodes[0]['name'] != '/') {
			array_unshift($nodes, array(
				'name' => '/', 
				'url' => '', 
				'title' => $titles[$this->get('session')->get('locale')]
			));
		}
		foreach ($nodes as &$node) {
			if ('/' == $node['name']) {
				$node['title'] = $titles[$this->get('session')->get('locale')];
			}	
			$node['ref'] = $this->getUrl($node);
		}
		
		return $nodes;
	}
	
	public function getNodeByName($name) {
		return $this->getTable('page')->getItem("name='".$name."'");
	}
	
	public function getNode($id) {
		return $this->getTable('page')->getItem($id);
	}
	
}
