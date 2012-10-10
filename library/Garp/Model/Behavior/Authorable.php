<?php
/**
 * Garp_Model_Behavior_Authorable
 * Fills the 'author_id' column with the user id of the person executing the write action.
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Behavior
 */
class Garp_Model_Behavior_Authorable extends Garp_Model_Behavior_Abstract {
	const _AUTHOR_COLUMN = 'author_id';
	const _MODIFIER_COLUMN = 'modifier_id';



	protected function _setup($config) {
	}


	/**
 	 * Before fetch callback.
 	 * @param Array $args
 	 * @return Void
 	 */
	public function beforeFetch(array &$args) {
		$model = $args[0];
		$select = &$args[1];
		if (Zend_Registry::isRegistered('CMS') && Zend_Registry::get('CMS')) {
			if (!Garp_Auth::getInstance()->isAllowed(get_class($model), 'fetch') &&
				Garp_Auth::getInstance()->isAllowed(get_class($model), 'fetch_own')) {
				$currentUserData = Garp_Auth::getInstance()->getUserData();
				$currentUserId   = $currentUserData['id'];
				$select->where(self::_AUTHOR_COLUMN.' = ?', $currentUserId);
			}
		}
	}


	/**
	 * Before insert callback. Manipulate the new data here.
	 * @param Array $options The new data is in $args[1]
	 * @return Array Or throw Exception if you wish to stop the insert
	 */
	public function beforeInsert(array &$args) {
		$data  = &$args[1];

		$auth = Garp_Auth::getInstance();
		if ($auth->isLoggedIn()) {
			$userData = $auth->getUserData();
			$data[self::_AUTHOR_COLUMN] = $userData['id'];
		}
	}


	public function beforeUpdate(&$args) {
		$data = &$args[1];

		$auth = Garp_Auth::getInstance();
		if ($auth->isLoggedIn()) {
			$userData = $auth->getUserData();
			$data[self::_MODIFIER_COLUMN] = $userData['id'];
		}
	}
}
