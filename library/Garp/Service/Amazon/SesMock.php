<?php
class Garp_Service_Amazon_SesMock extends Garp_Service_Amazon_Ses {
	protected static $_requests = array();

	public function _makeRequest($args = array()) {
		static::$_requests[] = $args;
	}

	public function getRequest($n) {
		return isset(static::$_requests[$n]) ? static::$_requests[$n] : null;
	}

	public function getRequests() {
		return static::$_requests;
	}

	public static function clearRequests() {
		static::$_requests = array();
	}

}
