<?php

namespace Fuga\CommonBundle\Model;

class SiteManager extends ModelManager
{
	public function detectSite($url)
	{
		$sites = null;

		if ($this->get('cache')->contains('global_sites')) {
			$sites = $this->get('cache')->fetch('global_sites');
		}

		if (!$sites) {
			$sites = $this->get('container')->getItems('config_version', '1=1', 'id DESC');
			$this->get('cache')->save('global_sites', $sites);
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