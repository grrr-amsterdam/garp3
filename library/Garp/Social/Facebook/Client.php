<?php
/**
 * Custom Facebook client.
 * Facebook's own client uses Sessions for storage, this one provides cookie support.
 * @author Harmen Janssen | grrr.nl
 * @see Facebook (/library/Garp/3rdParty/facebook/src/facebook.php)
 */
require_once APPLICATION_PATH.'/../library/Garp/3rdParty/facebook/src/base_facebook.php';
class Garp_Social_Facebook_Client extends BaseFacebook {
	/**
 	 * @var Garp_Store_Cookie
 	 */
	protected $_store;


	public function __construct($config) {
		$this->_store = new Garp_Store_Cookie('facebook');
		parent::__construct($config);
	}


	protected function setPersistentData($key, $value) {
		$this->_store->key = $value;
	}


	protected function getPersistentData($key, $default = false) {
		return $this->_store->key ?: $default;
	}


	protected function clearPersistentData($key) {
		$this->_store->destroy($key);
	}


	protected function clearAllPersistentData() {
		$this->_store->destroy();
	}
}
