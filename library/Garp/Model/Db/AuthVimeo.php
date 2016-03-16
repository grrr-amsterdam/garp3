<?php
/**
 * Garp_Model_Db_AuthVimeo
 * class description
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class Garp_Model_Db_AuthVimeo extends Model_Base_Vimeo {
	protected $_name = 'authvimeo';

	public function init() {
		parent::init();
		$this->registerObserver(new Garp_Model_Behavior_Authenticatable(array($this)));
	}

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

		$this->getObserver('Authenticatable')->updateLoginStats($userId);
		return $userData;
	}
}
