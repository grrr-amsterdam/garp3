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
 	 * @return Zend_Config
 	 */
	public function fetchAsConfig(Zend_Db_Select $select = null) {
		if (is_null($select)) {
			$select = $this->select();
		}
		$results = $this->fetchAll($select);

		// Create a multi-dimensional array from the ini-style keys
		$config = array();
		foreach ($results as $result) {
			$keyValue = "{$result->key} = \"{$result->value}\"\n";
			//$config[] = $this->_explodeIniKey($
		}

	}
}
