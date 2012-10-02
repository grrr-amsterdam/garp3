<?php
/**
 * Configuration scheme for binding models, which connect models in a hasAndBelongsToMany relation.
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Model_Spawn_Config_Model_Binding extends Garp_Model_Spawn_Config_Model_Abstract {
	public function __construct(
		$id,
		Garp_Model_Spawn_Config_Storage_Interface $storage,
		Garp_Model_Spawn_Config_Format_Interface $format
	) {
		parent::__construct($id, $storage, $format);

		$validator = new Garp_Model_Spawn_Config_Validator_Model_Binding();
		$validator->validate($this);
	}

}