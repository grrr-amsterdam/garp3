<?php
/**
 * Garp_Model_Helper
 * Abstract blueprint for all model helpers (validators, behaviors e.g.)
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Model
 * @lastmodified $Date: $
 */
abstract class Garp_Model_Helper extends Garp_Util_ObserverAbstract {
	/**
 	 * Configuration
 	 */
	protected $_config;

	/**
	 * Class constructor. Loads config.
	 * @param Array $config Configuration values.
	 * @return Void
	 */
	public function __construct($config = array()) {
		$this->_setup($config);
	}

	/**
	 * Setup the behavioral environment
	 * @param Array $config Configuration options
	 * @return Void
	 */
	protected function _setup($config) {
		$this->_config = $config;
	}
}
