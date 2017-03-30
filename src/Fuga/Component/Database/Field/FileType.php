<?php

namespace Fuga\Component\Database\Field;
    
class FileType extends Type
{
	public function __construct($params, $entity = null)
	{
		parent::__construct($params, $entity);
		$this->setParam('disallowed', ['.htaccess']);
	}

	public function getSQLValue($inputName = '')
	{
		$inputName = $inputName ? $inputName : $this->getName();
		$fileName = $this->dbValue;

		if ($this->container->get('request')->request->get($inputName.'_delete')) {
			$this->container->get('filestorage')->remove($fileName);
			$fileName = '';
		}

		if (!empty($_FILES[$inputName]['name'])
			&& !empty($_FILES[$inputName]['name'])
			&& !in_array($_FILES[$inputName]['name'], $this->getParam('disallowed'))
			&& !preg_match('/\.(php|js)$/i', $_FILES[$inputName]['name'])
		) {
			$this->container->get('filestorage')->remove($fileName);
			$fileName = $this->container->get('filestorage')->save($_FILES[$inputName]['name'], $_FILES[$inputName]['tmp_name']);
		} else {
			$fileName = '';
		}

		return $fileName;
	}
	
	public function getNativeValue()
	{
		return $this->container->get('filestorage')->path(parent::getNativeValue());
	}

	public function getStatic()
	{
		$content = '';

		if ($this->getNativeValue())
			$content = '<a href="'.$this->getNativeValue().'">'.$this->getNativeValue().'</a>&nbsp;('.$this->container->get('filestorage')->size($this->container->get('filestorage')->realPath(parent::getNativeValue())).')';

		return $content;
	}
	
	public function getInput($value = '', $name = '')
	{
		$name = $name ? $name : $this->getName();

		if ($s = $this->getStatic()) {
			$r = rand(0, getrandmax());
			$s = $s.'&nbsp;<label for="'.$r.'"><input name="'.$name.'_delete" type="checkbox" id="'.$r.'"> удалить</label>';
		}

		return '<input type="hidden" name="'.$name.'_oldValue" value="'.$this->dbValue.'">'.$s.'<input type="file" class="form-control" name="'.$name.'">';
	}

	public function free()
	{
		$this->container->get('filestorage')->remove($this->dbValue);
	}

}
