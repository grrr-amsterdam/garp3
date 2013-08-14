<?php
/**
 * @author David Spreekmeester | Grrr.nl
 */
class Garp_Model_Spawn_Config_Format_PhpArray implements Garp_Model_Spawn_Config_Format_Interface {

	/**
	 * Parses the raw configuration data.
	 * @param String $modelId The ID of this model
	 * @param Mixed $rawConfig Raw configuration data
	 * @param Boolean $allowEmpty Whether this configuration is allowed to be left empty
	 * @return Array Key / values configuration pairs
	 */
	public function parse($modelId, $rawConfig, $allowEmpty = false) {
		if (!is_null($rawConfig)) {
			$this->_validate($rawConfig, $allowEmpty);
			return $rawConfig;
		} else throw new Exception("The {$modelId} configuration is NULL.");
	}
	
	
	protected function _validate(array $config, $allowEmpty) {
		if (!$config && !$allowEmpty) {
			throw new Exception("The configuration is empty.");
		}
	}
}