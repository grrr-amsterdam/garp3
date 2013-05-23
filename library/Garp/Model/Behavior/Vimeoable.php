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
		if ($output = $this->_fillFields($data)) {
			$data = $output + $data;
		} else {
			throw new Garp_Model_Behavior_Exception('Could not properly retrieve API data from Vimeo.');
		}
	}
	
	
	/**
	 * Before update callback. Manipulate the new data here.
	 * @param Array $data The new data is in $args[1]
	 * @return Void
	 */
	public function beforeUpdate(array &$args) {
		$data = &$args[1];

		if ($output = $this->_fillFields($data)) {
			$data = $output + $data;
		} else {
			throw new Garp_Model_Behavior_Exception('Could not properly retrieve API data from Vimeo.');
		}
	}
	
	
	/**
	 * Retrieves additional data about the video corresponding with given input url from Vimeo, or video id, 
	 * and returns new data structure.
	 * @param Array $input New data
	 * @return Array
	 */
	protected function _fillFields(array $input) {
		$sourceApiKey = $this->_useVimeoPro ? 'advanced' : 'simple';
		if (array_key_exists($this->_fields[$sourceApiKey]['url'], $input)) {
			$url = $input[$this->_fields[$sourceApiKey]['url']];
			
			if (!empty($url)) {
				$entry = $this->_getVideo($url);
				if ($entry) {
					//print_r($entry); exit;
					$out = array();
					$source = $this->_fields[$sourceApiKey];
					foreach ($source as $vimeoKey => $garpKey) {
						// Note, the advanced API does not return a URL field, so pick it from the $input instead.
						if ($vimeoKey == 'url' && $this->_useVimeoPro) {
							$out[$garpKey] = $input[$garpKey];
							continue;
						}
						// Allow dot-notation to walk thru arrays
						if (strpos($vimeoKey, '.') !== false) {
							$keyParts = explode('.', $vimeoKey);
							$value = $entry;
							foreach ($keyParts as $key) {
								if (!array_key_exists($key, $value)) {
									throw new Garp_Model_Behavior_Exception('Given mapping does not exist: '.$vimeoKey);
								}
								$value = $value[$key];
							}
						} else {
							$value = $entry[$vimeoKey];
						}
						
						// allow overwriting of fields
						if (!empty($input[$garpKey]) && $this->_valueMaybeOverwritten($garpKey)) {
							$value = $input[$garpKey];
						}
						$out[$garpKey] = $value;
					}
					// if embedding is not allowed, hack our way around it.
					if (empty($out['player'])) {
						$out['player'] = 'http://player.vimeo.com/video/'.$entry['id'];
					}
					return $out;
				} else {
					throw new Garp_Model_Behavior_Exception('Video with url '.$url.' was not found.');
				}
			}
		} else {
			throw new Garp_Model_Behavior_Exception('Field '.$this->_fields['url'].' is mandatory.');
		}
	}
	
	
	/**
	 * Retrieve Vimeo video
	 * @param String $url Vimeo url
	 * @return Array
	 */
	protected function _getVideo($url) {
		if ($this->_useVimeoPro) {
			$vimeoVars = $this->_getVimeoConfig();
			$vimeo = new Garp_Service_Vimeo_Pro($vimeoVars->consumerKey, $vimeoVars->consumerSecret);

			// See if the currently logged in user has Vimeo credentials related to her, and use the token
			// and token secret. That way a user can fetch private videos thru the API.
			$garpAuth = Garp_Auth::getInstance();
			if ($garpAuth->isLoggedIn()) {
				$currentUser = $garpAuth->getUserData();
				$authVimeoModel = new G_Model_AuthVimeo();
				$authVimeoRecord = $authVimeoModel->fetchRow($authVimeoModel->select()->where('user_id = ?', $currentUser['id']));
				if ($authVimeoRecord) {
					$vimeo->setAccessToken($authVimeoRecord->access_token);
					$vimeo->setAccessTokenSecret($authVimeoRecord->access_token_secret);
				}
			}

			// check if a Vimeo URL is given
			if (preg_match('~vimeo.com/([0-9]+)~', $url, $matches)) {
				$videoId = $matches[1];
			} else {
				throw new Garp_Model_Behavior_Exception('Unable to distill Vimeo id from the given URL');
			}
			$video = $vimeo->videos->getInfo($videoId);
			return $video[0];
		} else {
			$vimeo = new Garp_Service_Vimeo();
			$video = $vimeo->video($url);
			return $video[0];
		}
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
			throw new Garp_Model_Behavior_Exception('Vimeo credentials are not configured in application.ini');
		}
		return $ini->auth->adapters->vimeo;
	}
}
