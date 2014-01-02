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
	protected $_name = 'auth_facebook';
	
	
	/**
	 * Store a new user. This creates a new auth_facebook record, but also
	 * a new user record.
	 * @param String $uid Facebook uid
	 * @param Array $props Properties received from Facebook
	 * @return Garp_Db_Table_Row The new user data
	 */
	public function createNew($uid, array $props) {		
		// first save the new user
		$userModel	= new Model_User();
		$userId		= $userModel->insert($props);
		$userData	= $userModel->find($userId)->current();		
		$this->insert(array(
			'facebook_uid'	=> $uid,
			'user_id'		=> $userId
		));
		
		$this->updateLoginStats($userId);
		return $userData;
	}
}