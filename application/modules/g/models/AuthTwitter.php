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
class G_Model_AuthTwitter extends Model_Base_AuthTwitter {
	protected $_name = 'authtwitter';

	public function init() {
		parent::init();
		$this->registerObserver(new Garp_Model_Behavior_Authenticatable(array($this)));
	}

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

		$this->getObserver('Authenticatable')->updateLoginStats($userId);
		return $userData;
	}
}
