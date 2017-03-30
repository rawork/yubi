<?php

namespace Fuga\CommonBundle\Manager;


class TemplateManager extends ModelManager
{
	protected $entityTable = 'template';
	protected $vars = [];
	protected $javascripts = [];
	protected $styles = [];

	public function getByNode($name)
	{
		$sql = "
			SELECT t.template 
			FROM template t 
			JOIN template_rule r ON t.id = r.template_id 
			WHERE r.locale= :locale AND (
				(r.type='0' AND r.cond='')
				OR (r.type = 'T' AND ((r.datefrom < NOW() AND r.datetill > NOW()) OR (r.datefrom = 0 AND r.datetill = 0))) 
				OR (r.type = 'U' AND LOCATE(r.cond, :url ) > 0)	
				OR (r.type = 'F' AND r.cond = :name )
			) ORDER BY sort DESC LIMIT 1";
		$stmt = $this->container->get('connection')->prepare($sql);
		$stmt->bindValue("locale", $this->container->get('session')->get('locale') ?: PRJ_LOCALE);
		$stmt->bindValue("url", $_SERVER['REQUEST_URI']);
		$stmt->bindValue("name", $name);
		$stmt->execute();
		$template = $stmt->fetch();

		if (!$template){
			throw new \Exception('Отсутствует шаблон для запрашиваемой страницы');
		}

		return $template['template'];
	}

	public function setVar($name, $value)
	{
		$this->vars[$name] = $value;
	}

	public function getVar($name)
	{
		return isset($this->vars[$name]) ? $this->vars[$name] : null;
	}

	public function getVars()
	{
		return $this->vars;
	}

	public function addJs($path)
	{
		if (!in_array($path, $this->javascripts)) {
			$this->javascripts[] = $path;
		}
	}

	public function getJs()
	{
		return $this->javascripts;
	}

	public function addCss($path)
	{
		if (!in_array($path, $this->styles)) {
			$this->styles[] = $path;
		}
	}

	public function getCss()
	{
		return $this->styles;
	}
}