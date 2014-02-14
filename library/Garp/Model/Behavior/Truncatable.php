<?php
/**
 * Garp_Model_Behavior_Truncatable
 * class description
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.0.1
 * @package      Garp_Model_Behavior
 */
class Garp_Model_Behavior_Truncatable extends Garp_Model_Behavior_Abstract {

	const CONFIG_KEY_COLUMNS = 'columns';

	/**
 	 * Column configuration
 	 * @var Array
 	 */
	protected $_config = array();

	/**
 	 * Setup behavior
 	 * @param Array $config
 	 */
	protected function _setup($config) {
		if (empty($config)) {
			throw new Garp_Model_Behavior_Exception('No config given');
		}
		if (empty($config[self::CONFIG_KEY_COLUMNS])) {
			throw new Garp_Model_Behavior_Exception('Missing required key ' . 
				self::CONFIG_KEY_COLUMNS);
		}

		$this->_config = $config;
	}

	public function beforeInsert(&$args) {
		$model = &$args[0];
		$data  = &$args[1];
		$this->_beforeSave($data);
	}
	
	public function beforeUpdate(&$args) {
		$model = &$args[0];
		$data  = &$args[1];
		$where = &$args[2];
		$this->_beforeSave($data);
	}
	
	protected function _beforeSave(array &$data) {
		foreach ($this->_config[self::CONFIG_KEY_COLUMNS] as $key => $maxLength) {
			$data[$key] = substr($data[$key], 0, $maxLength);
		}
	}

}
