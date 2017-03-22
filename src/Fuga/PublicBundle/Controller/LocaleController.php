<?php

namespace Fuga\PublicBundle\Controller;

use Fuga\CommonBundle\Controller\Controller;

class LocaleController extends Controller
{
	public function index()
	{
		$locales = $this->get('container')->getItems('config_version', 'publish=1');
		$currentLocale = $this->getManager('Fuga:Common:Locale')->getCurrentLocale();

		return $this->render('locale/public', compact('locales', 'currentLocale'));
	}
} 