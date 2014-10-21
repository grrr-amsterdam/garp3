<?php
/**
 * G_Model_AuthLinkedin
 * class description
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class G_Model_AuthLinkedin extends G_Model_Auth {
	protected $_name = 'authlinkedin'; 
	
	/**
	 * Store a new user. This creates a new auth_linkedin record, but also
	 * a new user record.
	 * @param String $linkedinId LinkedIn user id
	 * @param Array $props Properties received from LinkedIn
	 * @return Garp_Db_Table_Row The new user data
	 */
	public function createNew($linkedinId, array $props) {
		//print($linkedinId . '<pre>' . print_r($props, true));
		// first save the new user
		$userModel = new Model_User();
		$userId    = $userModel->insert($props);
		$userData  = $userModel->find($userId)->current();
		$this->insert(array(
			'linkedin_uid' => $linkedinId,
			'user_id' => $userId
		));
		
		$this->updateLoginStats($userId);
		return $userData;
	}
}
