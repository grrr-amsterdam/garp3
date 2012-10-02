<?php
/**
 * Garp_Service_Bitly
 * Bitly API wrapper. 
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Bitly
 * @lastmodified $Date: $
 */
class Garp_Service_Bitly extends Zend_Service_Abstract {
	/**
	 * API Url
	 * @var String
	 */
	const BITLY_API_URL = 'http://api.bit.ly/v3/';


	/**
	 * Login name
	 * @var String
	 */
	protected $_login;


	/**
	 * Api Key
	 * @var String
	 */
	protected $_apiKey;


	/**
	 * Class constructor
	 * @param String $login Login name
	 * @param String $apiKey API Key
	 * @return Void
	 */
	public function __construct($login = null, $apiKey = null) {
		$ini = Garp_Cache_Ini::factory(APPLICATION_PATH.'/configs/application.ini', APPLICATION_ENV);
		
		if (!$login) {
			if (!$login = $ini->bitly->login) {
				throw new Garp_Service_Bitly_Exception('No login was given, nor found in application.ini');
			}
		}

		if (!$apiKey) {
			if (!$apiKey = $ini->bitly->apiKey) {
				throw new Garp_Service_Bitly_Exception('No API key was given, nor found in application.ini');
			}
		}
		$this->_login = $login;
		$this->_apiKey = $apiKey;
	}

	
	/**
	 * Shorten a URL
	 * @param Array $params
	 * @return stdClass
	 */
	public function shorten(array $params) {
		$params = $params instanceof Garp_Util_Configuration ? $params : new Garp_Util_Configuration($params);
		$params->obligate('longUrl');
		return $this->request('shorten', (array)$params);
	}


	/**
	 * Send a request
	 * @param String $method
	 * @param Array $params
	 * @return String
	 */
	public function request($method, array $params) {
		// invariable parameters:
		$params['login'] = $this->_login;
		$params['apiKey'] = $this->_apiKey;
		$params['format'] = 'json'; 
		$params = http_build_query($params);

		$url = self::BITLY_API_URL.$method.'?'.$params;
		$response = $this->getHttpClient()
						 ->setMethod(Zend_Http_Client::GET)
						 ->setUri($url)
						 ->request()
		;
		return Zend_Json::decode($response->getBody());
	}
}
