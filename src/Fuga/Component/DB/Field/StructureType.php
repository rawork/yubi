<?php

namespace Fuga\Component\DB\Field;

class StructureType extends TextType
{
	public function __construct(&$params, $entity = null)
	{
		parent::__construct($params, $entity);
	}

//	public function getStatic()
// {
//		$content = array('<div class="admin-field-structure clearfix" id="'.$this->getParam('name').'">');
//
//		$content[] = '</div>';
//
//		return implode('', $content);
//	}

	public function getSQLValue($inputName = '')
	{
		$values = array();
		$params = $this->getParam('structure');

		// TODO написать сбор массива структуры и преобразование в json

		return json_encode($values);
 	}
	
	public function getNativeValue()
	{
		return json_decode(parent::getNativeValue(), true);
	}
	
	public function getInput($value = '', $name = '')
	{
		$name = $name ?: $this->getName();
		$params = $this->getParam('structure');
		$values = $this->getNativeValue();
		$content = array('');
		$num = 1;
		if (is_array($values)) {
			foreach ($values as $item) {
				$content[] = '<div class="structure-item">';
				// TODO рисуем блоки полей для данных
				foreach ($params as $key => $title) {
					$content[] = '<div class="form-group">
    <label for="'.$name.'_'.$key.'">'.$title.' '.$num.'</label>
    <textarea name="'.$name.'_'.$key.'[]" class="form-control" id="'.$name.'_'.$key.'" placeholder="'.$title.' '.$num.'">'.$item[$key].'</textarea>
  </div>';
				}
				$content[] = '</div>';
				$num++;
			}
		}
		$content[] = '<div class="structure-item">';
		// TODO рисуем блоки полей для данных
		foreach ($params as $key => $title) {
			$content[] = '<div class="form-group">
    <label for="'.$name.'_'.$key.'">'.$title.' '.$num.'</label>
    <textarea class="form-control" id="'.$name.'_'.$key.'" placeholder="'.$title.' '.$num.'"></textarea>
  </div>';
		}
		$content[] = '</div>';

		$content[] = '
<input class="btn btn-default btn-xs btn-add-input" data-name="'.$name.'" data-param="" value="Добавить..." type="button" />';

		return implode('', $content);
	}
	
}