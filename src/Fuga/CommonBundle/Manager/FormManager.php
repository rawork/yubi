<?php

namespace Fuga\CommonBundle\Manager;

use Fuga\Component\Templating\TwigTemplating;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use Symfony\Component\Security\Csrf\CsrfTokenManager;

use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Bridge\Twig\Extension\TranslationExtension;

class FormManager extends ModelManager
{
	protected $params;
	/**
	 * @var FormFactory
	 */
	protected $formFactory;
	/**
	 * @var TwigTemplating
	 */
	protected $twig;

	protected $formData = [];
	protected $fieldsData = [];

	public function getFactory()
	{
		if (!$this->formFactory) {
			$csrfGenerator = new UriSafeTokenGenerator();
			$csrfStorage = new SessionTokenStorage($this->container->get('session'));
			$csrfManager = new CsrfTokenManager($csrfGenerator, $csrfStorage);

			$defaultFormTheme = 'form/layout.html.twig';

			$this->twig = $this->container->get('templating');

			// create the Translator
			$translator = new Translator('ru');
			// somehow load some translations into it
			$translator->addLoader('xlf', new XliffFileLoader());
			$translator->addResource(
				'xlf',
				PRJ_DIR.'app/Resources/translations/validators.ru.xlf',
				'ru'
			);

			$this->twig->getEngine()->addExtension(new TranslationExtension($translator));

			$formEngine = new TwigRendererEngine(array($defaultFormTheme));
			$formEngine->setEnvironment($this->twig->getEngine());

			$this->twig->getEngine()->addExtension(new FormExtension(new TwigRenderer($formEngine, $csrfManager)));

			$this->formFactory = Forms::createFormFactoryBuilder()
				->addExtension(new CsrfExtension($csrfManager))
				->getFormFactory();
		}

		return $this->formFactory;
	}

	public function getTwig()
	{
		return $this->twig;
	}

	
	public function getForm($name)
	{
		$factory = $this->getFactory();

		$formData = $this->getTable('form')->getItem("name='$name' AND publish=1");
		if (!$formData) {
			return null;
		}

		$fieldsData = $this->getTable('form_field')->getItems('form_id='.$formData['id']);

		$builder = $this->formFactory->createNamedBuilder($formData['name']);

		foreach($fieldsData as $fieldData) {
			$builder->add($fieldData['name'], 'Symfony\\Component\\Form\\Extension\\Core\\Type\\'.ucfirst($fieldData['type']).'Type', ['label' => $fieldData['title'], 'required' => $fieldData['is_required'] == 1]);
		}

		$this->formData[$name] = $formData;
		$this->fieldsData[$name] = $fieldsData;

		return $builder->getForm();
	}

	public function send($name, $data)
	{
		$fields = [];

		foreach ($this->fieldsData[$name] as $field){
			$value = $data[$field['name']];
			if ($field['type'] == 'checkbox') {
				$value = (empty($value) ? 'нет' : 'да').'<br>';
			} elseif ($field['type'] == 'file' && is_array($_FILES) && isset($_FILES[$field['name']]) && $_FILES[$field['name']]['name'] != '') {
				$upfile = $_FILES[$field['name']];
				if ($upfile['name'] != '' && $upfile['size'] < MAX_FILE_SIZE ){
					$this->get('mailer')->Attach( $upfile['tmp_name'], $upfile['type'], 'inline', $upfile['name']);
				}
				$value = $upfile['name'].' см. вложение<br>';
			} else {
				$value = htmlspecialchars($value);
			}

			$fields[] = array('value' => $value, 'title' => $field['title']);
		}

		if ($this->formData[$name]['is_defense'] == 1) {
			$fields[] = array('value' => $this->get('request')->request->get('g-recaptcha-response'), 'title' => 'Код безопасности');
		}
		$this->container->get('mailer')->send(
			$this->formData[$name]['title'].' на сайте '.$_SERVER['SERVER_NAME'],
			$this->twig->render('form/mail', compact('fields')),
			empty($this->formData[$name]['email']) ? ADMIN_EMAIL : $this->formData[$name]['email']
		);

		return true;
	}

}
