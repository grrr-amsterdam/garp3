<?php
/**
 * G_Model_AuthFacebook
 * class description
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class G_Model_AuthFacebook extends G_Model_Auth {
	protected $_name = 'authfacebook';
	
	
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
		
		$this->updateLoginStats($userId);
		return $userData;
	}
}
