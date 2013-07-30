<?php
/**
 * Garp_Social_Facebook
 * Wrapper around Facebook functionality
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Social
 * @lastmodified $Date: $
 */
class Garp_Social_Facebook {
	/**
 	 * Facebook SDK
 	 * @var Facebook
 	 */
	protected $_client;


	/**
 	 * Class constructor
 	 * @param Array $config
 	 * @return Void
 	 */
	private function __construct(array $config) {
		$config = new Garp_Util_Configuration($config);
		$config->obligate('appId')
			   ->obligate('secret');
		$this->_client = $this->_getClient((array)$config);
	}


	/**
 	 * Singleton interface
 	 * @param Array $config
 	 * @return Garp_Social_Facebook
 	 */
	public static function getInstance(array $config) {
		return new Garp_Social_Facebook($config);
	}


	/**
 	 * Get URL to login page
 	 * @return String
 	 */
	public function getLoginUrl() {
		return $this->_client->getLoginUrl();
	}


	/**
 	 * Get access token
 	 * @return String
 	 */
	public function getAccessToken() {
		return $this->_client->getAccessToken();
	}


	/**
 	 * Log a user in
 	 * @return Array Userdata
 	 * @throws FacebookApiException
 	 */
	public function login() {
		$uid = $this->_client->getUser();		
		$accessToken = $this->_client->getAccessToken();

		// If a user is authenticated, $userData will be filled with user data
		$userData = $this->_client->api('/me');
		$userData['access_token'] = $accessToken;
		return $userData;
	}


	/**
 	 * Find friends of logged in user and map to local friends table.
 	 * @param Array $config
 	 * @return Bool Success
 	 */
	public function mapFriends(array $config) {
		$config = $config instanceof Garp_Util_Configuration ? $config : new Garp_Util_Configuration($config);
		$config->obligate('bindingModel')
			->obligate('user_id')
			->setDefault('accessToken', $this->getAccessToken())
		;

		if (!$config['accessToken']) {
			// Find the auth record
			$authModel = new G_Model_AuthFacebook();
			$authRow   = $authModel->fetchRow($authModel->select()->where('user_id = ?', $config['user_id']));
			if (!$authRow || !$authRow->access_token) {
				return false;
			}

			// Use the stored access token to create a user session. Me() in the FQL ahead will contain the user's Facebook ID.
			// Note that the access token is available for a very limited time. Chances are it's not valid anymore.
			$accessToken = $authRow->access_token;
		}
		try {
			$this->_client->setAccessToken($config['accessToken']);
			
			// Find the friends' Facebook UIDs
			$friends = $this->_client->api(array(
				'method' => 'fql.query',
				'query'  => 'SELECT uid2 FROM friend WHERE uid1 = me()'
			));

			// Find local user records
			$userModel = new Model_User();
			$userTable = $userModel->getName();
			$authFbModel = new G_Model_AuthFacebook();
			$authFbTable = $authFbModel->getName();

			$fbIds = '';
			$friendCount = count($friends);
			foreach ($friends as $i => $friend) {
				$fbIds .= $userModel->getAdapter()->quote($friend['uid2']);
				if ($i < ($friendCount-1)) {
					$fbIds .= ',';
				}
			}
			$friendQuery = $userModel->select()
				->setIntegrityCheck(false)
				->from($userTable, array('id'))
				->join($authFbTable, $authFbTable.'.user_id = '.$userTable.'.id', array())
				->where('facebook_uid IN ('.$fbIds.')')
				->order($userTable.'.id')
			;
			$localUsers = $userModel->fetchAll($friendQuery);
			$localUserCount = count($localUsers);

			// Insert new friendships into binding model
			$bindingModel = new $config['bindingModel'];
			$insertSql = 'INSERT IGNORE INTO '.$bindingModel->getName().' (user1_id, user2_id) VALUES ';
			foreach ($localUsers as $i => $localUser) {
				$insertSql .= '('.$localUser->id.','.$config['user_id'].'),';
				$insertSql .= '('.$config['user_id'].','.$localUser->id.')';
				if ($i < ($localUserCount-1)) {
					$insertSql .= ',';
				}
			}
			$result = $bindingModel->getAdapter()->query($insertSql);
			// Clear cache manually, since the table isn't updated thru conventional paths.
			Garp_Cache_Purgatory::purge($bindingModel);
			return !!$result;
		} catch (Exception $e) {
			return false;
		}
	}


	/**
 	 * Maps to Facebook::api().
 	 * @param Array $params
 	 * @return Mixed
 	 */
	public function api(array $params) {
		return $this->_client->api($params);
	}


	/**
 	 * Create Facebook SDK
 	 * @param Array $config
 	 * @return Facebook
 	 */
	protected function _getClient(array $config) {
		$ini = Zend_Registry::get('config');
		$type = !empty($ini->store->type) ? $ini->store->type : 'Session';
		if ($type == 'Cookie') {
			return new Garp_Social_Facebook_Client($config);
		} else {
			require_once APPLICATION_PATH.'/../garp/library/Garp/3rdParty/facebook/src/facebook.php';
			$facebook = new Facebook($config);
			return $facebook;
		}
	}
}
