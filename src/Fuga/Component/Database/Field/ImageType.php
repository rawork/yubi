<?php

namespace Fuga\Component\Database\Field;

class ImageType extends FileType
{
	public function __construct($params, $entity = null)
	{
		parent::__construct($params, $entity);
	}

	public function setParams($params = [])
	{
		parent::setParams($params);

		if ($sizes = $this->getParam('sizes')) {
			$sizes["default"] = ["width" => 50, "height" => 50, "adaptive" => true];
			$this->setParam('sizes', $sizes);
		} else {
			$this->setParam('sizes', []);
		}

		$this->setParam('allowed', ['image/gif', 'image/png', 'image/jpg', 'image/jpeg', 'image/svg+xml']);
	}

	public function getStatic()
	{
		$value = $this->getNativeValue();
		$fileName = $value['value'];
		$additionalFiles = '';
		if (isset($value['extra'])) {
			foreach ($value['extra'] as $file) {
				if ($file['name'] == 'default') {
					$fileName = $file['path'];
				} else {
					$additionalFiles .= '<div><a target="_blank" href="'.$file['path'].'">'.ucfirst($file['name']).' image</a> ('.$file['size'].')</div>';
				}
			}
		}
		return $fileName ? '<a target="_blank" href="'.parent::getNativeValue().'"><img width="50" src="'.$fileName.'"></a><div>('.$this->container->get('imagestorage')->size($this->dbValue).')</div>'.$additionalFiles : '';
	}
	
	public function getGroupStatic() {
		$value = $this->getNativeValue();
		$fileName = isset($value['extra']['default']) ? $value['extra']['default']['path'] : $value['value'];
		return $fileName ? '<a target="_blank" href="'.$fileName.'"><img width="50" src="'.$fileName.'"></a>' : '';
	}

	public function getSQLValue($inputName = '')
	{
		$this->container->get('imagestorage')->setOptions(['sizes' => $this->getParam('sizes')]);
		$inputName = $inputName ? $inputName : $this->getName();
		$fileName = $this->dbValue;

		if ($fileName && $this->container->get('request')->request->get($inputName.'_delete')) {
			$this->container->get('imagestorage')->remove($fileName);
			$fileName = '';
		}

		if (!empty($_FILES[$inputName])
			&& !empty($_FILES[$inputName]['name'])
			&& in_array($_FILES[$inputName]['type'], $this->getParam('allowed'))
		) {
			$this->container->get('imagestorage')->remove($fileName);
			$fileName = $this->container->get('imagestorage')->save($_FILES[$inputName]['name'], $_FILES[$inputName]['tmp_name']);
		} else {
			$fileName = '';
		}

		return $fileName;
	}
	
	public function getNativeValue()
	{
		$value = array('value' => parent::getNativeValue());
		if ($value['value']) {
			if ($files = $this->container->get('imagestorage')->additionalFiles($this->dbValue, ['sizes' => $this->getParam('sizes')])) {
				$value['extra'] = $files;
			}
		}
		
		return $value;			
	}
	
	public function free()
	{
		$this->container->get('imagestorage')->remove($this->dbValue);
	}

}
