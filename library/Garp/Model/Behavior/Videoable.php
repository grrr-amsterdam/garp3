<?php
/**
 * Garp_Model_Behavior_Videoable
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Behavior
 * @lastmodified $Date: $
 */
class Garp_Model_Behavior_Videoable extends Garp_Model_Behavior_Abstract {
	/**
	 * Config. Field translation table is in 'fields'. Keys are internal names, values are the indexes of the output array.
	 * Vimeo keys must be in $_config['fields']['vimeo'], YouTube keys must be in $_config['fields']['youtube'].
	 * @var Array
	 */
	protected $_config;


	/**
 	 * Setup the behavior - this configures the keys used to map service data to local database data.
 	 * Make sure to add a "vimeo" and a "youtube" key for the respective services. 
 	 * @see Garp_Model_Behavior_Youtubeable and @see Garp_Model_Behavior_Vimeoable for the default mapping.
 	 * @param Array $config
 	 * @return Void
 	 */
	protected function _setup($config) {
		if (empty($config['vimeo'])) {
			$config['vimeo'] = array();
		}
		if (empty($config['youtube'])) {
			$config['youtube'] = array();
		}
		$this->_config = $config;
	}
	
	
	/**
 	 * Before insert callback
 	 * @param Array $args
 	 * @return Void
 	 */
	public function beforeInsert(&$args) {
		$this->_beforeSave($args, 'beforeInsert');
	}


	/**
 	 * Before update callback
 	 * @param Array $args
 	 * @return Void
 	 */
	public function beforeUpdate(&$args) {
		$this->_beforeSave($args, 'beforeUpdate');
	}


	/**
 	 * Custom callback for both insert and update
 	 * @param Array $args
 	 * @param String $event
 	 * @return Void
 	 */
	protected function _beforeSave(&$args, $event) {
		$data = &$args[1];
		if (!empty($data['url'])) {
			$url = $data['url'];
			if ($this->_isYouTubeUrl($url)) {
				// check for YouTube
				$behavior = new Garp_Model_Behavior_YouTubeable($this->_config['youtube']);
				$data['type'] = 'youtube';
			} elseif ($this->_isVimeoUrl($url)) {
				// check for Vimeo
				$behavior = new Garp_Model_Behavior_Vimeoable($this->_config['vimeo']);
				$data['type'] = 'vimeo';
			} else {
				throw new Garp_Model_Exception('Invalid URL given. It was not recognized as either YouTube or Vimeo.');
			}
			$behavior->{$event}($args);
		}
	}


	/**
 	 * Check if a URL is a YouTube URL
 	 * @param String $url
 	 * @return Boolean
 	 */
	protected function _isYouTubeUrl($url) {
		return false !== strpos($url, 'youtube.com') || false !== strpos($url, 'youtu.be');
	}


	/**
 	 * Check if a URL is a Vimeo URL
 	 * @param String $url
 	 * @return Boolean
 	 */
	protected function _isVimeoUrl($url) {
		return false !== strpos($url, 'vimeo.com');
	}
}
