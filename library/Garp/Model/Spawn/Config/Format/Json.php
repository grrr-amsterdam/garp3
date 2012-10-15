<?php
/**
 * @author David Spreekmeester | Grrr.nl
 */
class Garp_Model_Spawn_Config_Format_Json implements Garp_Model_Spawn_Config_Format_Interface {

	/**
	 * Parses the raw configuration data.
	 * @param String $modelId The ID of this model
	 * @param Mixed $rawConfig Raw configuration data
	 * @param Boolean $allowEmpty Whether this configuration is allowed to be left empty
	 * @return Array Key / values configuration pairs
	 */
	public function parse($modelId, $rawConfig, $allowEmpty = false) {
		$config = json_decode($rawConfig, true);

		if (!is_null($config)) {
			$this->_validate($config, $allowEmpty);
			return $config;
		} else throw new Exception("The {$modelId} configuration file contains invalid JSON. Don't forget the double quotes around array keys and string-like values. Or use jsonlint.com to validate your file.");
	}
	
	
	protected function _validate(array $config, $allowEmpty) {
		if (!$config && !$allowEmpty) {
			throw new Exception("The configuration is empty. Don't forget the double quotes around array keys and string-like values.");
		} elseif (array_key_exists('id', $config)) {
			throw new Exception("The 'id' property cannot be defined in the model configuration file, but is provided separately.");
		}
	}
}