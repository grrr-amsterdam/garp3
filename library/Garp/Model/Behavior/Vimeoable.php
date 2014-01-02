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
		//	internal name	=> database / form name
		'id'              => 'identifier',
		'title'           => 'name',
		'description'     => 'description',
		'url'             => 'url',
		'duration'        => 'duration',
		'tags'            => 'tags',
		'thumbnail_large'	=> 'image',
		'thumbnail_small'	=> 'thumbnail',
		'user_name'       => 'author',
	);


	/**
	 * Setup fields. If certain fields are not provided, 
	 * the defaults in $this->_fields are used.
	 * @param Array $config
	 * @return Void
	 */
	protected function _setup($config) {
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
	protected function _fillFields(Array $input) {
		if (array_key_exists($this->_fields['url'], $input)) {
			$url = $input[$this->_fields['url']];
			
			if (!empty($url)) {
				$entry = $this->_getVideo($url);
				$entry = $entry[0];
				if ($entry) {
					$out = array();
					foreach ($this->_fields as $vimeoKey => $garpKey) {
						// allow overwriting of fields
						$out[$garpKey] = $entry[$vimeoKey];
						if (!empty($input[$garpKey]) && $this->_valueMaybeOverwritten($garpKey)) {
							$out[$garpKey] = $input[$garpKey];
						}
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
		$vimeo = new Garp_Service_Vimeo();
		return $vimeo->video($url);
	}
	
	
	/**
	 * Check if the user is allowed to overwrite a certain value
	 * @param String $key
	 * @return Boolean
	 */
	protected function _valueMaybeOverwritten($key) {
		return in_array($key, array('name', 'description'));
	}
}
