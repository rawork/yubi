<?php

namespace Fuga\CommonBundle\Model;

class UserManager extends ModelManager {
	
	protected $entityTable = 'user_user';
	private $currentUser;

	public function getCurrentUser() {
		if (!$this->currentUser){
			$this->currentUser = $this->get('security')->getCurrentUser();
			if ($this->currentUser) {
				$field = $this->get('container')->getTable('user_user')->getFieldType($this->get('container')->getTable('user_user')->fields['avatar']);
				$this->currentUser['avatar_extra'] = $this->get('imagestorage')->additionalFiles(
					$this->currentUser['avatar'],
					['sizes' => $field->getParam('sizes')]
				);
				$this->currentUser['avatar'] = $this->currentUser['avatar'] ? UPLOAD_REF.$this->currentUser['avatar'] : '';
				$this->currentUser['ship_id'] = isset($this->currentUser['ship_id']) ? $this->currentUser['ship_id'] : 0;
				$this->currentUser['role_id'] = isset($this->currentUser['role_id']) ? $this->currentUser['role_id'] : 0;
				if ($this->currentUser['ship_id']) {
					$this->currentUser['ship'] = $this->get('container')->getItem('crew_ship', $this->currentUser['ship_id']);
				}
				if ($this->currentUser['role_id']) {
					$this->currentUser['role'] = $this->get('container')->getItem('pirate_prof', $this->currentUser['role_id']);
				}
				unset($this->currentUser['password']);
				unset($this->currentUser['hash']);
				unset($this->currentUser['token']);
			}
		}

		return $this->currentUser;
	}

	public function getPirates()
	{
		return $this->get('container')->getItems('user_user', 'group_id='.GAMER_GROUP.' AND is_active=1');
	}
}