<?php
/**
 * Garp_Model_Validator_Abstract
 * Blueprint for Garp_Entity validators
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Validator
 * @lastmodified $Date: $
 */
abstract class Garp_Model_Validator_Abstract extends Garp_Model_Helper {

	/**
	 * Validate wether the given columns are not empty
	 * @param Array $data The data to validate
	 * @param Boolean $onlyIfAvailable Wether to skip validation on fields that are not in the array
	 * @return Void
	 * @throws Garp_Model_Validator_Exception
	 */
	abstract public function validate(array $data, Garp_Model_Db $model, $onlyIfAvailable = false);

	/**
	 * BeforeInsert callback.
	 * @param Array $args The new data is in $args[1]
	 * @return Void
	 */
	public function beforeInsert(&$args) {
		$model = &$args[0];
		$data  = &$args[1];
		$this->validate($data, $model);
	}

	/**
	 * BeforeUpdate callback.
	 * @param Array $args The new data is in $args[1]
	 * @return Void
	 */
	public function beforeUpdate(&$args) {
		$model = &$args[0];
		$data  = &$args[1];
		$where = &$args[2];
		$this->validate($data, $model, true);
	}

}
