<?php

namespace Fuga\PublicBundle\Controller;

use Fuga\CommonBundle\Controller\Controller;

// TODO Cache of search results

class SearchController extends Controller {
	
	function index() {
		$searchText = $this->get('request')->query->get('text');
		
		if ($searchText) {
			$results = $this->get('search')->getResults($searchText);
			if (count($results)) {
				$this->page = $this->get('request')->query->getInt('page', 1);
				$maxPerPage = 20;
				$pagesQuantity = ceil(count($results)/$maxPerPage);
				$ptext = '';
				if ($pagesQuantity > 1){
					$ptext = '<div>';
					if ($this->page > 1) {
						$ref = '?text='.urlencode($searchText).'&page='.($this->page-1);
						$ptext .= '<a title="назад" href="'.$ref.'">&larr;</a>';
					}
					for ($i = 1; $i<=$pagesQuantity; $i++){
						$ptext .= $i == $this->page ? ' '.$i.' ' : ' <a href="?text='.urlencode($searchText).'&page='.$i.'">'.$i.'</a> ';
					}
					if ($this->page < $pagesQuantity) {
						$ref = '?text='.urlencode($searchText).'&page='.($this->page+1);
						$ptext .= '<a title="вперед" href="'.$ref.'">&rarr;</a>';
					}
					$ptext .= '</div>';
					
				}
				if ($this->page == $pagesQuantity &&  (sizeof($results) % $maxPerPage) > 0) {
					$max_per_page_cur = count($results) % $maxPerPage;
				} else {
					$max_per_page_cur = $maxPerPage;
				}
				$items = [];
				for ($i = 1; $i <= $max_per_page_cur; $i++) {
					$j = $i+($this->page-1)*$maxPerPage;
					$results[$j-1]['num'] = $j;
					$items[] = $results[$j-1];
				}
				$content .= $this->render('search/list', compact('ptext', 'items', 'searchText'));
			} else {
				$content .= $this->render('search/empty', compact('searchText'));
			}
		}
		$content = $this->render('search/form', compact('searchText')).$content;
		return $content;
	}

	public function form()
	{
		return $this->render('search/form');
	}
}
