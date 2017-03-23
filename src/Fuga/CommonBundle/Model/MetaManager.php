<?php

namespace Fuga\CommonBundle\Model;

class MetaManager extends ModelManager {
	
	protected $entityTable = 'page_seo';
	
	public function getMeta($uri = false) {
		return $this->getItem('meta', $uri);
	}
	
	public function getTitle($uri = false) {
		return $this->getItem('title', $uri);
	}
	
	public function getItem($field, $uri = false) {
		if (!$uri) {
			$uri = $_SERVER['REQUEST_URI'];
		}
		$items = $this->getTable($this->entityTable)->getItems();
		foreach ($items as $item) {
			$words = explode(',', $item['words']);
			foreach ($words as $w) {
				$w = trim($w);
				if (!empty($w) && $uri == $w) {
					return $item[$field];
				}
			}
			$keywords = explode(',', $item['keywords']);
			foreach ($keywords as $w) {
				$w = trim($w);
				if (!empty($w) && stristr($uri, $w)) {
					return $item[$field];
				}
			}
		}
	}
}