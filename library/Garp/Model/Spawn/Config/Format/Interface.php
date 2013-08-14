<?php
/**
 * @author David Spreekmeester | Grrr.nl
 */
interface Garp_Model_Spawn_Config_Format_Interface {

	/**
	 * Parses the raw configuration data.
	 * @param String $modelId The ID of this model
	 * @param Mixed $rawConfig Raw configuration data
	 * @param Boolean $allowEmpty Whether this configuration is allowed to be left empty
	 * @return Array Key / values configuration pairs
	 */
	public function parse($modelId, $rawConfig, $allowEmpty = false);
}