<?php
/**
 * Garp_Service_MailChimp
 * MailChimp wrapper
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage MailChimp
 * @lastmodified $Date: $
 */
class Garp_Service_MailChimp extends Zend_Service_Abstract {
	/**
	 * The MailChimp API key.
	 * Find it at http://www.mailchimp.com/kb/article/where-can-i-find-my-api-key
	 * @var String
	 */
	protected $_apiKey = '';
	
	
	/**
	 * Datacenter id (found at the end of the api key)
	 * @var String
	 */
	protected $_datacenterId;
	
	
	/**
	 * Response output type 
	 * @var String
	 */
	protected $_outputType = 'json';
	
	
	/**
	 * Class constructor
	 * @return Void
	 */
	public function __construct($apiKey = null) {
		$this->_apiKey = $apiKey ?: $this->_getApiKey();
		$datacenterId = explode('-', $this->_apiKey, 2);
		
		if (!empty($datacenterId[1])) {
			$this->_datacenterId = $datacenterId[1];
		} else {
			throw new Garp_Service_MailChimp_Exception('Api key is invalid - no datacenter id was found in it.');
		}
	}
	
	
	/**
	 * Subscribe the provided email to a list.
	 * @param Array|Garp_Util_Configuration $options
	 * @return StdClass
	 */
	public function listSubscribe($options) {
		if (!$options instanceof Garp_Util_Configuration) {
			$options = new Garp_Util_Configuration($options);
		}
		$options->obligate('email_address')
				->obligate('id')
				->setDefault('merge_vars', array('LNAME' => '', 'FNAME' => ''))
				;
		$options['method'] = 'listSubscribe';
		return $this->_send((array)$options);
	}
	
	
	/**
	 * Create a new user template, NOT campaign content. 
	 * @param String $name The name for the template - names must be unique and a max of 50 bytes
	 * @param String $html A string specifying the entire template to be created. 
	 * @return StdClass
	 */
	public function templateAdd($name, $html) {
		return $this->_send(array(
			'name' => $name,
			'html' => $html,
			'method' => 'templateAdd'
		));
	}
	
	
	/**
	 * Generic entry point for making API calls
	 * @param String $method
	 * @param Array $params 
	 * @return StdClass The json_decoded response
	 */
	public function __call($method, $params) {
		$params = $params[0];
		$params['method'] = $method;
		return $this->_send($params);
	}
	
	
	/**
	 * Send a request
	 * @param Array $options All the GET options
	 * @return StdClass The json_decoded response
	 */
	protected function _send(array $options) {
		$options['apikey'] = $this->_apiKey;
		$get = array(
			'method' => $options['method'],
			'output' => $this->_outputType
		);
		unset($options['method']);
		
		$uri  = 'http://'.$this->_datacenterId.'.api.mailchimp.com/1.3/?';
		$uri .= http_build_query($get);

		$response = $this->getHttpClient()
						 ->setParameterPost($options)
						 ->setMethod(Zend_Http_Client::POST)
						 ->setUri($uri)
						 ->request()
		;
		$response = Zend_Json::decode($response->getBody());
		return $response;
	}
	
	
	/**
	 * Get API key from ini file
	 * @return String
	 */
	protected function _getApiKey() {
		$ini = Zend_Registry::get('config');
		if (!$apiKey = $ini->mailchimp->apiKey) {
			throw new Garp_Service_MailChimp_Exception('No API key was given, nor found in application.ini');
		}
		return $apiKey;
	}
}
