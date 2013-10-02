<?php
/**
 * Garp_Service_Elasticsearch_Configuration
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Elasticsearch
 * @lastmodified $Date: $
 */
class Garp_Service_Elasticsearch_Configuration {
	const ERROR_NO_APP_NAME_CONFIGURED =
		'There is no app name configured, and no custom Elasticsearch index name was provided.';
	const ERROR_NO_BASE_URL_CONFIGURED =
		'I did not find the elasticsearch.baseUrl configuration. It should contain the ES url, without index name and trailing slash.';
	const ERROR_APP_NAME_EMPTY =
		'The configured app name was empty, so it cannot be used as a default Elasticsearch index name.';

	/**
	 * @var String $_baseUrl
	 */
	protected $_baseUrl;
	
	/**
	 * @var String $_index
	 */
	protected $_index;
		
	/**
	 * @return String
	 */
	public function getBaseUrl() {
		return $this->_baseUrl;
	}

	/**
	 * @return String
	 */
	public function getIndex() {
		return $this->_index;
	}

	/**
	 * @param 	Array 	$params 				Parameters to start this ES instance.
	 * 			String	[$params['baseUrl']]	The url to the ES instance, excluding index name and trailing slash. 
	 * 			String	[$params['index']]		The index name to use. Defaults to an ini configured value.
	 */
	public function __construct(array $params = array()) {
		$params = $this->_addDefaults($params);
		$this->_loadParams($params);
	}

	protected function _addDefaults(array $params) {
		if (!array_key_exists('index', $params)) {
			$params['index'] = $this->_getDefaultIndex();
		}

		if (!array_key_exists('baseUrl', $params)) {
			$params['baseUrl'] = $this->_getDefaultBaseUrl();
		}

		return $params;
	}

	protected function _loadParams(array $params) {
		foreach ($params as $paramName => $paramValue) {
			$prop = '_' . $paramName;
			$this->$prop = $paramValue;
		}
	}

	/**
	 * Returns the database name for this environment, to be used as the index name for Elasticsearch.
	 */
	protected function _getDefaultIndex() {
		$config = Zend_Registry::get('config');

		if (!isset($config->app->name)) {
			throw new Exception(self::ERROR_NO_APP_NAME_CONFIGURED);
		}

		$appName = str_replace(' ', '', $config->app->name);
		$appName = Garp_Util_String::camelcasedToDashed($appName);

		if (!$appName) {
			throw new Exception(self::ERROR_APP_NAME_EMPTY);
		}

		$indexName = $appName . '-' . APPLICATION_ENV;

		return $indexName;
	}

	protected function _getDefaultBaseUrl() {
		$config = Zend_Registry::get('config');

		if (!isset($config->elasticsearch->baseUrl)) {
			throw new Exception(self::ERROR_NO_BASE_URL_CONFIGURED);
		}

		return $config->elasticsearch->baseUrl;
	}
}
