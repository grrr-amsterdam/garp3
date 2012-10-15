<?php
/**
 * @author David Spreekmeester | Grrr.nl
 */
class Garp_Model_Spawn_Config_Storage_PhpArray implements Garp_Model_Spawn_Config_Storage_Interface {
	/**
	 * @var Array $_config The provided configuration array
	 */
	protected $_config;


	/**
	 * @param Array $config Associative array with model IDs as keys, and the model configuration as their value.
	 */
	public function __construct(array $config) {
		if ($config) {
			$this->_config = $config;
		} else {
			throw new Exception("The configuration array is empty.");
		}
	}


	/**
	 * @param String $objectId The object ID.
	 * @return Array The configuration content
	 */
	public function load($objectId) {
		if (array_key_exists($objectId, $this->_config)) {
			return $this->_config[$objectId];
		} else throw new Exception("Sorry, could not find the config for {$objectId} in the PhpArray.");
	}


	public function listObjectIds() {
		return array_keys($this->_config);
	}
}