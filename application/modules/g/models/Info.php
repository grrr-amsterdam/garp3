<?php
/**
 * Garp_Model_Info
 * Generic info model. Allows admins to configure various parameters about their system.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      G_Model
 */
class G_Model_Info extends Model_Base_Info {
	/**#@+
	 * Constants that are used for types of info
	 * @var string
	 */
	const INFO = 'info';
	const SETTING = 'setting';
	const MESSAGE = 'message';
    /**#@-*/


	/**
 	 * Fetch results and converts to Zend_Config object
 	 * @param Zend_Db_Select $select A select object to filter results
 	 * @param String $env Which application env to use
 	 * @return Zend_Config
 	 */
	public function fetchAsConfig(Zend_Db_Select $select = null, $env = APPLICATION_ENV) {
		if (is_null($select)) {
			$select = $this->select();
		}
		$results = $this->fetchAll($select);

		$ini = $this->_createIniFromResults($results, $env);
		return $ini;
	}


	/**
 	 * Create a Zend_Config instance from database results
 	 * @param Zend_Db_Table_Rowset $results
 	 * @param Array $config The array that's recursively filled with the right keys
 	 * @return Zend_Config
 	 */
	protected function _createIniFromResults($results, $env) {
		// Create a parse_ini_string() compatible string.
		$ini = "[$env]\n";
		foreach ($results as $result) {
			$keyValue = "{$result->key} = \"{$result->value}\"\n";
			$ini .= $keyValue;
		}

		$iniObj = Garp_Config_Ini::fromString($ini);
		return $iniObj;
	}
}
