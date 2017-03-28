<?php

namespace Fuga\Component\Database\Field;

class SelectTreeType extends LookUpType
{
	public function __construct($params, $entity = null)
	{
		parent::__construct($params, $entity);
	}

	public function getStatic($value = null)
	{
		$value = $value ?: parent::getNativeValue();

		if ($value) {
			$sql = 'SELECT id,'.$this->getParam('l_field').' FROM '.$this->getParam('l_table').' WHERE id='.intval($value);
			$stmt = $this->get('connection')->prepare($sql);
			$stmt->execute();
			$entity = $stmt->fetch();
			if ($this->getParam('l_field') && count($entity)) {
				$ret = '';
				$fields = explode(',', $this->getParam('l_field'));
				foreach ($fields as $field_name)
					if (!empty($entity[$field_name]))
						$ret .= ($ret ? ' ' : '').$entity[$field_name];
				return $ret.' ('.$entity['id'].')';
			} else {
				return 'Элемент #'.$entity['id'];
			}
		}

		return 'Не выбрано';
	}
	
	public function getNativeValue()
	{
		$value = array('value' => parent::getNativeValue());

		if (!empty($value['value'])) {
			$sql = 'SELECT * FROM '.$this->getParam('l_table').' WHERE id IN('.$value['value'].')';
			$stmt = $this->get('connection')->prepare($sql);
			$stmt->execute();
			$item = $stmt->fetch();
			if ($item) {
				$value['item'] = array();
				foreach ($item as $k => $v) {
					$value['item'][$k] = $v;
				}
			}
		}

		if ('many' == $this->getParam('link_type')  && $this->dbId) {
			$sql = 'SELECT
				t1.id as id,t1.'.$this->getParam('l_field').' as '.$this->getParam('l_field').'
				FROM '.$this->getParam('link_table').' t0
				JOIN '.$this->getParam('l_table').' t1 ON t0.'.$this->getParam('link_mapped').'=t1.id
				WHERE t0.'.$this->getParam('link_inversed').'='.$this->dbId;
			$stmt = $this->get('connection')->prepare($sql);
			$stmt->execute();
			$entities = $stmt->fetchAll();
			$extra = array();
			foreach ($entities as $entity) {
				$extra[] = $entity['id'];
			}
			$value['extra'] = [];
			if ($extra) {
				$value['extra'] = $this->get('container')
					->getManager('Fuga:Common:Table')
					->getByName($this->getParam('l_table'))
					->getItems('id IN('.implode(',', $extra).')');
			}
		}

		return $value;
	}

	public function getInput($value = '', $name = '')
	{
		return $this->select_tree_getInput($value, $name);
	}

	public function getSearchInput()
	{
		return $this->select_tree_getInput(parent::getSearchValue(), parent::getSearchName());
	}

	protected function select_tree_getInput($value, $name)
	{
		$name = $name ?: $this->getName();
		$value = empty($value) ? intval($this->dbValue) : $value;
		$table = $this->getParam('table');
		$id = empty($this->dbId) ? '-1' : $this->dbId;
		$input_id = strtr($name, '[]', '__');
		$extra = array();
		$extraElements = array();
		if ('many' == $this->getParam('link_type')  && $this->dbId) {
			$sql = 'SELECT 
				t1.id as id,t1.'.$this->getParam('l_field').' as '.$this->getParam('l_field').'
				FROM '.$this->getParam('link_table').' t0 
				JOIN '.$this->getParam('l_table').' t1 ON t0.'.$this->getParam('link_mapped').'=t1.id
				WHERE t0.'.$this->getParam('link_inversed').'='.$this->dbId.' AND '.$this->getParam('link_mapped').'<>'.parent::getNativeValue();
			$stmt = $this->get('connection')->prepare($sql);
			$stmt->execute();
			$entities = $stmt->fetchAll();
			foreach ($entities as $entity) {
				$extraElements[] = '<div>'.$this->getStatic($entity['id']).' <input type="radio" name="'.$input_id.'_default" value="'.$entity['id'].'" class="selected-default" data-input="'.$input_id.'">  По умолчанию <a href="#" class="selected-remove" data-input="'.$input_id.'"><i class="glyphicon glyphicon-remove"></i></a></div>';
				$extra[] = $entity['id'];
			}
		}
		$extra = implode(',', $extra);
		$extraElements = implode('', $extraElements);
		$staticValue = $this->getStatic($value);
		$defaultValue = $value ? '  '.('many' == $this->getParam('link_type') ? '<input type="radio" name="'.$input_id.'_default" value="'.$value.'" class="selected-default" data-input-id="'.$input_id.'" checked> По умолчанию': '').' <a href="#" class="selected-remove" data-input="'.$input_id.'"><i class="glyphicon glyphicon-remove"></i></a>' : '';
		$ret = '
<div id="'.$input_id.'_title">
<div>'.$staticValue.$defaultValue.'</div> 
'.$extraElements.'	
</div>
<button class="btn btn-success btn-tree-dialog" data-url="'.$this->get('routing')->getGenerator()->generate('admin_dialog_tree').'" data-input="'.$input_id.'" data-table="'.$table.'" data-field="'.$name.'" data-value="'.$id.'" data-title="'.htmlspecialchars($this->getStatic($value)).'">Выбрать</button>
<input type="hidden" name="'.$name.'" value="'.$value.'" id="'.$input_id.'">
<input type="hidden" name="'.$name.'_extra" value="'.$extra.'" id="'.$input_id.'_extra">	
<input type="hidden" name="'.$name.'_type" value="'.$this->getParam('link_type').'" id="'.$input_id.'_type">
';

		return $ret;
	}

}
