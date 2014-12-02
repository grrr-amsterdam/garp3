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
	const ERROR_NO_BASE_READ_URL_CONFIGURED =
		'I did not find the elasticsearch.read.baseUrl configuration. It should contain the ES url, without index name and trailing slash.';
	const ERROR_NO_BASE_WRITE_URL_CONFIGURED =
		'I did not find the elasticsearch.write.baseUrl configuration. It should contain the ES url, without index name and trailing slash.';
	const ERROR_APP_NAME_EMPTY =
		'The configured app name was empty, so it cannot be used as a default Elasticsearch index name.';

	/**
	 * @var String $_readBaseUrl
	 */
	protected $_readBaseUrl;
	
	/**
	 * @var String $_writeBaseUrl
	 */
	protected $_writeBaseUrl;

	/**
	 * @var String $_index
	 */
	protected $_index;
		
	/**
	 * @return String
	 */
	public function getReadBaseUrl() {
		return $this->_readBaseUrl;
	}

	/**
	 * @return String
	 */
	public function getWriteBaseUrl() {
		return $this->_writeBaseUrl;
	}

	/**
	 * @return String
	 */
	public function getIndex() {
		return $this->_index;
	}

	/**
	 * @param 	Array 	$params 					Parameters to start this ES instance.
	 * 			String	[$params['readBaseUrl']]	The url to the ES instance, excluding index name and trailing slash. 
	 * 			String	[$params['writeBaseUrl']]	The url to the ES instance, excluding index name and trailing slash. 
	 * 			String	[$params['index']]			The index name to use. Defaults to an ini configured value.
	 */
	public function __construct(array $params = array()) {
		$params = $this->_addDefaults($params);
		$this->_loadParams($params);
	}

	protected function _addDefaults(array $params) {
		if (!array_key_exists('index', $params)) {
			$params['index'] = $this->_getDefaultIndex();
		}

		if (!array_key_exists('readBaseUrl', $params)) {
			$params['readBaseUrl'] = $this->_getDefaultReadBaseUrl();
		}

		if (!array_key_exists('writeBaseUrl', $params)) {
			$params['writeBaseUrl'] = $this->_getDefaultWriteBaseUrl();
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

	protected function _getDefaultReadBaseUrl() {
		$config = Zend_Registry::get('config');

		if (!isset($config->elasticsearch->readBaseUrl)) {
			throw new Exception(self::ERROR_NO_BASE_READ_URL_CONFIGURED);
		}

		return $config->elasticsearch->readBaseUrl;
	}

	protected function _getDefaultWriteBaseUrl() {
		$config = Zend_Registry::get('config');

		if (!isset($config->elasticsearch->writeBaseUrl)) {
			throw new Exception(self::ERROR_NO_BASE_WRITE_URL_CONFIGURED);
		}

		return $config->elasticsearch->writeBaseUrl;
	}
}
