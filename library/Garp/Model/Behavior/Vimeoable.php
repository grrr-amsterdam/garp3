<?php
/**
 * Garp_Model_Behavior_Vimeoable
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Behavior
 * @lastmodified $Date: $
 */
class Garp_Model_Behavior_Vimeoable extends Garp_Model_Behavior_Abstract {
	/**#@+
	 * Various exceptions
 	 * @var String
 	 */
	const EXCEPTION_NO_API_DATA = 'Could not properly retrieve API data from Vimeo.';
	const EXCEPTION_MISSING_FIELD = 'Field %s is mandatory.';
	const EXCEPTION_VIDEO_NOT_FOUND = 'Video with url %s was not found.';
	const EXCEPTION_UNDEFINED_MAPPING = 'Given mapping does not exist: %s';
	const EXCEPTION_MISSING_VIMEO_ID = 'Unable to distill Vimeo id from the given URL';
	const EXCEPTION_MISSING_VIMEO_CREDENTIALS = 'Vimeo credentials are not configured in application.ini';
    /**#@-*/

	/**
	 * Field translation table. Keys are internal names, values are the indexes of the output array.
	 * @var Array
	 */
	protected $_fields = array(
		'simple' => array(
			//	internal name	=> database / form name
			'id'              => 'identifier',
			'title'           => 'name',
			'description'     => 'description',
			'url'             => 'url',
			'duration'        => 'duration',
			'tags'            => 'tags',
			'thumbnail_large' => 'image',
			'thumbnail_small' => 'thumbnail',
			'user_name'       => 'video_author',
		),
		'advanced' => array(
			'id'              => 'identifier',
			'title'           => 'name',
			'description'     => 'description',
			'duration'        => 'duration',
			'thumbnails.thumbnail.0._content' => 'thumbnail',
			'thumbnails.thumbnail.2._content' => 'image',
			'owner.display_name' => 'video_author',
			'url'             => 'url'
		)
	);

	/**
 	 * Wether to use the Vimeo Pro service
 	 * @var Boolean
 	 */
	protected $_useVimeoPro = false;

	/**
	 * Setup fields. If certain fields are not provided, 
	 * the defaults in $this->_fields are used.
	 * @param Array $config
	 * @return Void
	 */
	protected function _setup($config) {
		$this->_useVimeoPro = !empty($config['useVimeoPro']) && $config['useVimeoPro'];
		unset($config['useVimeoPro']);

		if (!empty($config)) {
			$this->_fields = $config + $this->_fields;
		}
	}
	
	/**
	 * Before insert callback. Manipulate the new data here. Set $data to FALSE to stop the insert.
	 * @param Array $options The new data is in $args[1]
	 */
	public function beforeInsert(array &$args) {
		$data = &$args[1];
		if (!$output = $this->_fillFields($data)) {
			throw new Garp_Model_Behavior_Exception(self::EXCEPTION_NO_API_DATA);
		}
		$data = $output + $data;
	}

	/**
	 * Before update callback. Manipulate the new data here.
	 * @param Array $data The new data is in $args[1]
	 * @return Void
	 */
	public function beforeUpdate(array &$args) {
		$data = &$args[1];
		if (!$output = $this->_fillFields($data)) {
			throw new Garp_Model_Behavior_Exception(self::EXCEPTION_NO_API_DATA);
		}
		$data = $output + $data;
	}

	/**
	 * Retrieves additional data about the video corresponding with given input url from Vimeo, or video id, 
	 * and returns new data structure.
	 * @param Array $input New data
	 * @return Array
	 */
	protected function _fillFields(array $input) {
		$sourceApiKey = $this->_useVimeoPro ? 'advanced' : 'simple';
		if (!array_key_exists($this->_fields[$sourceApiKey]['url'], $input)) {
			throw new Garp_Model_Behavior_Exception(sprintf(self::EXCEPTION_MISSING_FIELD, $this->_fields['url']));
		}
		$url = $input[$this->_fields[$sourceApiKey]['url']];
		if (empty($url)) {
			return $input;
		}

		$videoData = $this->_getVideo($url);
		if (!$videoData) {
			throw new Garp_Model_Behavior_Exception(sprintf(self::EXCEPTION_VIDEO_NOT_FOUND, $url));
		}

		$out = array();
		$source = $this->_fields[$sourceApiKey];
		foreach ($source as $vimeoKey => $garpKey) {
			$this->_addDataKey($out, $input, $videoData, $vimeoKey, $garpKey);
		}

		// if embedding is not allowed, hack our way around it.
		if (empty($out['player'])) {
			$out['player'] = 'http://player.vimeo.com/video/' . $videoData['id'];
		}
		return $out;
	}

	/**
 	 * Populate data with Vimeo data
 	 * @param Array $output The array to populate
 	 * @param Array $input The input given from the insert/update call
 	 * @param Array $videoData Video data retrieved from Vimeo
 	 * @param String $vimeoKey Key used by Vimeo
 	 * @param String $garpKey Key used by Garp
 	 * @return Void
 	 */
	protected function _addDataKey(&$output, $input, $videoData, $vimeoKey, $garpKey) {
		// Note, the advanced API does not return a URL field, so pick it from the $input instead.
		if ($vimeoKey == 'url' && $this->_useVimeoPro) {
			$output[$garpKey] = $input[$garpKey];
			return;
		}

		$value = $this->_extractValue($videoData, $vimeoKey);
		
		// allow overwriting of fields
		if (!empty($input[$garpKey]) && $this->_valueMaybeOverwritten($garpKey)) {
			$value = $input[$garpKey];
		}

		$this->_populateOutput($output, $garpKey, $value);
	}

	/**
 	 * Extract value from Vimeo video data
 	 * @param Array $videoData
 	 * @param String $key
 	 * @return String
 	 */
	protected function _extractValue(array $videoData, $key) {
		// Allow dot-notation to walk thru arrays
		if (strpos($key, '.') === false) {
			return $videoData[$key];
		}
		$keyParts = explode('.', $key);
		$key = current($keyParts);
		$value = $videoData;
		while (is_array($value) && array_key_exists($key, $value)) {
			$value = $value[$key];
			$key = next($keyParts);
		}
		if (is_array($value)) {
			throw new Garp_Model_Behavior_Exception(sprintf(self::EXCEPTION_UNDEFINED_MAPPING, $key));			
		}
		return $value;
	}

	/**
 	 * Populate record with new data
 	 * @param Array $output
 	 * @param String $key
 	 * @param String $value
 	 * @return Void
 	 */
	protected function _populateOutput(array &$output, $key, $value) {
		if (strpos($key, '.') === false) {
			$output[$key] = $value;
			return;
		}
		$array = Garp_Util_String::toArray($key, '.', $value);
		$output += $array;
	}
	
	/**
	 * Retrieve Vimeo video
	 * @param String $url Vimeo url
	 * @return Array
	 */
	protected function _getVideo($url) {
		return $this->_useVimeoPro ? $this->_getProVideo($url) : $this->_getRegularVideo($url);
	}

	/**
 	 * Retrieve Vimeo video from the Pro API
 	 * @param String $url Vimeo url
 	 * @return Array
 	 */
	protected function _getProVideo($url) {
		$vimeoVars = $this->_getVimeoConfig();
		$vimeo = new Garp_Service_Vimeo_Pro($vimeoVars->consumerKey, $vimeoVars->consumerSecret);
		$this->_setVimeoAccessToken($vimeo);

		// check if a Vimeo URL is given
		preg_match('~vimeo.com/([0-9]+)~', $url, $matches);
		if (!isset($matches[1])) {
			throw new Garp_Model_Behavior_Exception(self::EXCEPTION_MISSING_VIMEO_ID);
		}
		$videoId = $matches[1];
		$video = $vimeo->videos->getInfo($videoId);
		return $video[0];
	}

	/**
 	 * Set Vimeo access token
 	 * @param Garp_Service_Vimeo_Pro $vimeo
 	 * @return Void
 	 */
	protected function _setVimeoAccessToken(Garp_Service_Vimeo_Pro $vimeo) {
		// See if the currently logged in user has Vimeo credentials related to her, and use the token
		// and token secret. That way a user can fetch private videos thru the API.
		$garpAuth = Garp_Auth::getInstance();
		if (!$garpAuth->isLoggedIn()) {
			return;
		}
		$currentUser = $garpAuth->getUserData();
		$authVimeoModel = new G_Model_AuthVimeo();
		$authVimeoRecord = $authVimeoModel->fetchRow($authVimeoModel->select()->where('user_id = ?', $currentUser['id']));
		if ($authVimeoRecord) {
			$vimeo->setAccessToken($authVimeoRecord->access_token);
			$vimeo->setAccessTokenSecret($authVimeoRecord->access_token_secret);
		}
	}		

	/**
 	 * Retrieve Vimeo video from the regular API.
 	 * @param String $url Vimeo url
 	 * @return Array
 	 */
	protected function _getRegularVideo($url) {
		$vimeo = new Garp_Service_Vimeo();
		$video = $vimeo->video($url);
		return $video[0];
	}

	/**
	 * Check if the user is allowed to overwrite a certain value
	 * @param String $key
	 * @return Boolean
	 */
	protected function _valueMaybeOverwritten($key) {
		return in_array($key, array('name', 'description'));
	}

	/**
 	 * @return Zend_Config_Ini
 	 */
	protected function _getVimeoConfig() {
		$ini = Zend_Registry::get('config');
		if (empty($ini->auth->adapters->vimeo->consumerKey) || empty($ini->auth->adapters->vimeo->consumerSecret)) {
			throw new Garp_Model_Behavior_Exception(self::EXCEPTION_MISSING_VIMEO_CREDENTIALS);
		}
		return $ini->auth->adapters->vimeo;
	}
}
