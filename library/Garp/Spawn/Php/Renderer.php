<?php
/**
 * Generated PHP model
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Model
 * @lastmodified $Date: $
 */
class Garp_Spawn_Php_Renderer {
		
	/**
	 * @var Garp_Spawn_Model_Abstract $_model
	 */
	protected $_model;
	


	public function __construct(Garp_Spawn_Model_Abstract $model) {
		$this->setModel($model);
	}


	public function save() {
		$model		= $this->getModel();
		$factory 	= new Garp_Spawn_Php_Model_Factory($model);
		
		$baseModel = $factory->produce(Garp_Spawn_Php_Model_Factory::TYPE_BASE);
		$baseModel->save();

		$extendedModel = $factory->produce(Garp_Spawn_Php_Model_Factory::TYPE_EXTENDED);
		$extendedModel->save();
		
		if ($habtmRelations = $model->relations->getRelations('type', 'hasAndBelongsToMany')) {
			array_walk($habtmRelations, array($this, '_saveBindingModel'));
		}
		
		if ($model->isMultilingual()) {
			$this->_saveLocalizedModels();
		}
		
		new Garp_Spawn_Php_ModelsIncluder($model->id);
	}
	
	protected function _saveBindingModel(Garp_Spawn_Relation $habtmRelation, $relationName) {
		$model 			= $this->getModel();
		$bindingModel 	= $habtmRelation->getBindingModel();
		$factory		= new Garp_Spawn_Php_Model_Factory($model);

		$bindingPhpModel = $factory
			->produce(Garp_Spawn_Php_Model_Factory::TYPE_BINDING_BASE, $habtmRelation);
		$bindingPhpModel->save();

		$bindingExtendedPhpModel = $factory
			->setModel($bindingModel)
			->produce(Garp_Spawn_Php_Model_Factory::TYPE_EXTENDED);
		$bindingExtendedPhpModel->save();
	}
	
	protected function _saveLocalizedModels() {
		$locales = Garp_I18n::getLocales();
		array_walk($locales, array($this, '_saveLocalizedModel'));
	}
	
	protected function _saveLocalizedModel($locale) {
		$model 		= $this->getModel();
		$factory 	= new Garp_Spawn_Php_Model_Factory($model);
		$localizedModel = $factory->produce(Garp_Spawn_Php_Model_Factory::TYPE_LOCALIZED, $locale);
		$localizedModel->save();
	}
		
	/**
	 * @return Garp_Spawn_Model_Abstract
	 */
	public function getModel() {
		return $this->_model;
	}
	
	/**
	 * @param Garp_Spawn_Model_Abstract $model
	 */
	public function setModel($model) {
		$this->_model = $model;
	}
}
