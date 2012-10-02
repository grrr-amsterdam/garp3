<?php
/**
 * G_Model_AuthTwitter
 * class description
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class G_Model_AuthTwitter extends G_Model_Auth {
	protected $_name = 'AuthTwitter';
	
	
	/**
	 * Store a new user. This creates a new auth_twitter record, but also
	 * a new user record.
	 * @param String $twitterId Twitter user id
	 * @param Array $props Properties received from Twitter
	 * @return Garp_Db_Table_Row The new user data
	 */
	public function createNew($twitterId, array $props) {		
		// first save the new user
		$userModel	= new Model_User();
		$userId		= $userModel->insert($props);
		$userData	= $userModel->find($userId)->current();		
		$this->insert(array(
			'twitter_uid' => $twitterId,
			'user_id' => $userId
		));
		
		$this->updateLoginStats($userId);
		return $userData;
	}
}
