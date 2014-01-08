<?php
/**
 * Garp_Model_Behavior_Timestampable
 * Autofill timestamp fields such as "created" and "modified"
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Behavior
 * @lastmodified $Date: $
 */
class Garp_Model_Behavior_Timestampable extends Garp_Model_Behavior_Abstract {
	/**
	 * Date format
	 * @var String
	 */
	const DATE_FORMAT = 'Y-m-d H:i:s';
	
	 
	/**
	 * Fields to work on
	 * @var Array
	 */
	protected $_fields;
	
	
	/**
	 * Make sure the config array is at least filled with some default values to work with.
	 * @param Array $config Configuration values
	 * @return Void
	 */
	protected function _setup($config) {
		if (empty($config['createdField'])) {
			$config['createdField'] = 'created';
		}
		if (empty($config['modifiedField'])) {
			$config['modifiedField'] = 'modified';
		}
		$this->_fields = $config;
	}
	
	
	/**
	 * Before insert callback. Manipulate the new data here.
	 * @param Array $options The new data is in $args[1]
	 * @return Array Or throw Exception if you wish to stop the insert
	 */
	public function beforeInsert(array &$args) {
		$data = &$args[1];
		$data[$this->_fields['createdField']]  = date(self::DATE_FORMAT);
		$data[$this->_fields['modifiedField']] = date(self::DATE_FORMAT);
	}
	
	
	/**
	 * Before update callback. Manipulate the new data here.
	 * @param Array $data The new data is in $args[1]
	 * @return Void
	 */
	public function beforeUpdate(array &$args) {
		$data = &$args[1];
		$data[$this->_fields['modifiedField']] = date(self::DATE_FORMAT);
	}
}