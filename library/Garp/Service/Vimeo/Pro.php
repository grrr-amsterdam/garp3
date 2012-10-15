<?php
/**
 * Garp_Service_Vimeo_Pro
 * Vimeo Pro API wrapper.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Vimeo
 * @lastmodified $Date: $
 */
class Garp_Service_Vimeo_Pro extends Zend_Service_Abstract {
	/**
	 * API Url
	 * @var String
	 */
	const VIMEO_API_URL = 'http://vimeo.com/api/rest/v2';


	/**
 	 * Response format (json/xml/php)
 	 * @var String
 	 */
	const RESPONSE_FORMAT = 'json';


	/**
 	 * API methods
 	 * @var Array
 	 */
	protected static $_methods = array(
		'activity',
		'albums',
		'channels',
		'contacts',
		'groups',
		'oauth',
		'people',
		'test',
		'videos'
	);


	/**
 	 * Consumer key
 	 * @var String
 	 */
	protected $_consumerKey;


	/**
 	 * Consumer secret
 	 * @var String
 	 */
	protected $_consumerSecret;


	/**
 	 * Access token, required for certain methods
 	 * @var String
 	 */
	protected $_accessToken;


	/**
 	 * Access token secret, required for certain methods
 	 * @var String
 	 */
	protected $_accessTokenSecret;


	/**
 	 * Class constructor
 	 * @param String $consumerKey
 	 * @param String $consumerSecret 
 	 * @param String $accessToken
 	 * @param String $accessTokeeSecret
 	 * @return Void
 	 */
	public function __construct($consumerKey, $consumerSecret, $accessToken = null, $accessTokenSecret = null) {
		$this->setConsumerKey($consumerKey);
		$this->setConsumerSecret($consumerSecret);

		if ($accessToken) {
			$this->setAccessToken($accessToken);
		}
		if ($accessTokenSecret) {
			$this->setAccessTokenSecret($accessTokenSecret);
		}
	}


	/**
 	 * Get method group object
 	 * @param String $key
 	 * @return Garp_Service_Vimeo_Pro_Method
 	 */
	public function __get($key) {
		if (in_array($key, Garp_Service_Vimeo_Pro::$_methods)) {
			$className = 'Garp_Service_Vimeo_Pro_Method_'.ucfirst($key);
			return new $className($this);
		}
		throw new Garp_Service_Vimeo_Exception('Method group '.$key.' not found.');
	}


	/**
 	 * Set consumer key
 	 * @param String $key
 	 * @return $this
 	 */
	public function setConsumerKey($consumerKey) {
		$this->_consumerKey = $consumerKey;
		return $this;
	}


	/**
 	 * Get consumer key
 	 * @return String
 	 */
	public function getConsumerKey() {
		return $this->_consumerKey;
	}


	/**
 	 * Set consumer secret
 	 * @param String $secret
 	 * @return $this
 	 */
	public function setConsumerSecret($consumerSecret) {
		$this->_consumerSecret = $consumerSecret;
		return $this;
	}


	/**
 	 * Get consumer secret
 	 * @return String
 	 */
	public function getConsumerSecret() {
		return $this->_consumerSecret;
	}


	/**
 	 * Set access token
 	 * @param String $accessToken
 	 * @return $this
 	 */
	public function setAccessToken($accessToken) {
		$this->_accessToken = $accessToken;
		return $this;
	}


	/**
 	 * Get access token
 	 * @return String
 	 */
	public function getAccessToken() {
		return $this->_accessToken;
	}


	/**
 	 * Set access token secret
 	 * @param String $accessTokenSecret
 	 * @return $this
 	 */
	public function setAccessTokenSecret($accessTokenSecret) {
		$this->_accessTokenSecret = $accessTokenSecret;
		return $this;
	}


	/**
 	 * Get access token secret
 	 * @return String
 	 */
	public function getAccessTokenSecret() {
		return $this->_accessTokenSecret;
	}


	/**
	 * Send a request
	 * @param String $method Methodname
	 * @param Array $queryParams GET parameters
	 * @return Array
	 */
	public function request($method, array $queryParams) {
		$queryParams['format'] = self::RESPONSE_FORMAT;
		if (!substr($method, 0, 5) != 'vimeo') {
			$method = 'vimeo.'.$method;
		}
		$queryParams['method'] = $method;
		$queryString = http_build_query($queryParams);
		$url = self::VIMEO_API_URL.'?'.$queryString;

		$oAuthHttpUtility = new Zend_Oauth_Http_Utility();
		$params = array(
            'oauth_consumer_key'     => $this->getConsumerKey(),
            'oauth_nonce'            => $oAuthHttpUtility->generateNonce(),
            'oauth_timestamp'        => $oAuthHttpUtility->generateTimestamp(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_version'          => '1.0'
        );
		
		if ($this->getAccessToken()) {
			$params['oauth_token'] = $this->getAccessToken();
		}
		
        $params['oauth_signature'] = $oAuthHttpUtility->sign(
            array_merge($queryParams, $params),
            'HMAC-SHA1',
            $this->getConsumerSecret(),
            $this->getAccessTokenSecret(),
            Zend_Oauth::GET,
            self::VIMEO_API_URL
        );

		$httpClient = $this->getHttpClient()
				 		   ->setHeaders('Authorization', $oAuthHttpUtility->toAuthorizationHeader($params))
		                   ->setMethod(Zend_Http_Client::GET)
		                   ->setUri($url);
		$response = $httpClient->request()->getBody();
		$response = json_decode($response, true);
		if ($response['stat'] == 'fail') {
			$error = 'An unknown error occurred at Vimeo.';
			if (!empty($response['err']['expl'])) {
				$error = $response['err']['expl'];
			}
			throw new Garp_Service_Vimeo_Exception($response['err']['expl']);
		}
		return $response;
	}
}
