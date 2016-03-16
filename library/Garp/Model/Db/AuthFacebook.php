<?php
/**
 * Garp_Model_Db_AuthFacebook
 * class description
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class Garp_Model_Db_AuthFacebook extends Model_Base_AuthFacebook {
	protected $_name = 'authfacebook';

	public function init() {
		parent::init();
		$this->registerObserver(new Garp_Model_Behavior_Authenticatable(array($this)));
	}

	/**
	 * Store a new user. This creates a new auth_facebook record, but also
	 * a new user record.
	 * @param Array $authData Data for the new Auth record
	 * @param Array $userData Data for the new User record
	 * @return Garp_Db_Table_Row The new user data
	 */
	public function createNew(array $authData, array $userData) {
		// first save the new user
		$userModel	= new Model_User();
		$userId		= $userModel->insert($userData);
		$userData	= $userModel->find($userId)->current();
		$authData['user_id'] = $userId;
		$this->insert($authData);

		$this->getObserver('Authenticatable')->updateLoginStats($userId);
		return $userData;
	}
}
