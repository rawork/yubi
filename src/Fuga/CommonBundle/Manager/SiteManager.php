<?php

namespace Fuga\CommonBundle\Manager;

class SiteManager extends ModelManager
{
	public function detectSite($url)
	{
		$sites = null;

		if ($this->container->get('cache')->contains('global.sites')) {
			$sites = $this->container->get('cache')->fetch('global.sites');
		}

		if (!$sites) {
			$sites = $this->getTable('site_version')->getItems('1=1', 'id DESC');
			$this->container->get('cache')->save('global.sites', $sites);
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