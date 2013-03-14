<?php
/**
 * G_Model_AuthVimeo
 * class description
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class G_Model_AuthVimeo extends G_Model_Auth {
	/**
	 * Store a new user. This creates a new AuthVimeo record, but also
	 * a new user record.
	 * @param String $vimeoId Vimeo user id
	 * @param Zend_Oauth_Token_Access $accessToken oAuth access token
	 * @param Array $props Properties received from Vimeo
	 * @return Garp_Db_Table_Row The new user data
	 */
	public function createNew($vimeoId, Zend_Oauth_Token_Access $accessToken, array $props) {
		// first save the new user
		$userModel	= new Model_User();
		$userId		= $userModel->insert($props);
		$userData	= $userModel->find($userId)->current();		
		$this->insert(array(
			'vimeo_id'            => $vimeoId,
			'access_token'        => $accessToken->getToken(),
			'access_token_secret' => $accessToken->getTokenSecret(),
			'user_id'             => $userId
		));
		
		$this->updateLoginStats($userId);
		return $userData;
	}
}
