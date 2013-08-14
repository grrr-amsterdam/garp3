<?php
/**
 * Validation for a model configuration.
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Model_Spawn_Config_Validator_Model_Base extends Garp_Model_Spawn_Config_Validator_Model_Abstract {
	protected $_mandatoryProps = array('id', 'inputs');
	
	
	public function validate(ArrayObject $config) {
		parent::validate($config);
		
		$this->_verifyHabtmIsConfiguredFromCorrectModel($config);
	}
	
	
	protected function _verifyHabtmIsConfiguredFromCorrectModel(ArrayObject $config) {
		if (array_key_exists('relations', $config)) {
			foreach ($config['relations'] as $relationName => $relation) {
				if (
					array_key_exists('type', $relation) &&
					$relation['type'] === 'hasAndBelongsToMany'
				) {
					$relaterModelName = $config['id'];
					$relateeModelName = array_key_exists('model', $relation) ? $relation['model'] : $relationName;
					if (strcmp($relaterModelName, $relateeModelName) > 0) {
						throw new Exception("You've configured a hasAndBelongsToMany relation {$relaterModelName} > {$relateeModelName}, but it should be configured in alphabetical order. Please configure it as {$relateeModelName} > {$relaterModelName}.");
					}
				}
			}
		}
	}
}