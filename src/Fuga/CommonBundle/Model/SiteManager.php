<?php

namespace Fuga\CommonBundle\Model;

class SiteManager extends ModelManager
{
	public function detectSite($url)
	{
		$sites = null;

		if ($this->get('cache')->contains('global.sites')) {
			$sites = $this->get('cache')->fetch('global.sites');
		}

		if (!$sites) {
			$sites = $this->getTable('site_version')->getItems('1=1', 'id DESC');
			$this->get('cache')->save('global.sites', $sites);
		}

		foreach($sites as $site) {
			if (strpos($url, $site['folder']) === 0) {
				$site['url'] = '/'.substr($url, strlen($site['folder']));
				return $site;
			}
		}

		return array(
			'title' => 'default',
			'folder' => PRJ_REF.'/',
			'url' => $url,
			'language' => PRJ_LOCALE
		);
	}
} 