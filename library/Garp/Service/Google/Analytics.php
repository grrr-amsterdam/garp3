<?php

class Garp_Service_Google_Analytics {

	const GOOGLE_ANALYTICS_URL = 'https://www.google-analytics.com/collect';
	const GOOGLE_ANALYTICS_DEBUG_URL = 'https://www.google-analytics.com/debug/collect';
	const GOOGLE_ANALYTICS_COOKIE_NAME = '_ga';

	protected $_version = 1;

	protected $_trackerId;

	protected $_clientId;

	protected $_client;

	public function __construct($trackerId = null) {
		$this->_client = new Zend_Http_Client(self::GOOGLE_ANALYTICS_URL);
		$this->_trackerId = $trackerId ?: $this->_getTrackerIdFromConfig();
	}

	/**
	 * for all possible parameters check:
	 * https://developers.google.com/analytics/devguides/collection/protocol/v1/parameters
	 */
	public function send($type, $params) {
		if (empty($params['t']) && $type !== null) {
			$params['t'] = $type;
		}
		$params = $this->_appendDefaultParameters($params);
		$this->_client->setParameterPost($params);
		
		return $this->_client->request('POST', $params);
	}

	protected function _appendDefaultParameters($params) {
		if (empty($params['v'])) {
			$params['v'] = $this->_version;
		}
		if (empty($params['tid'])) {
			$params['tid'] = $this->_trackerId;
		}
		if (empty($params['cid'])) {
			$params['cid'] = $this->_getClientId();
		}
		return $params;	
	}

	protected function _getTrackerIdFromConfig() {
		$config = Zend_Registry::get('config');
		return $config->google->analytics->id;
	}

	protected function _getClientId() {
		// @todo: try to extract client id from cookie
		$clientId = mt_rand(10, 1000).round(microtime(true));
		
		return $clientId;
	}

}