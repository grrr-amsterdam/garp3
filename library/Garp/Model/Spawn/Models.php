<?php
/**
 * Generated datamodel
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Model
 */
class Garp_Model_Spawn_Models {
	private static $_instance;
	

	public static function getInstance() {
		if (!self::$_instance) {
			self::$_instance = self::_buildModels();
		}
		return self::$_instance;
	}
	
	
	protected static function _buildModels() {
		if ($modelIds = Garp_Model_Spawn_ConfigFile::findAll()) {
			$models = array();

			foreach ($modelIds as $modelId) {
				$models[$modelId] = new Garp_Model_Spawn_Model($modelId);
			}
			
			if (count($models)) {
			
				Garp_Model_Spawn_Relations::defineDefaultRelations($models);
				Garp_Model_Spawn_Relations::defineHasAndBelongsToMany($models);
				Garp_Model_Spawn_Relations::defineHasMany($models);

				return $models;
			}
		}

		throw new Exception('Couldn\'t find any configurated models.');
	}
}