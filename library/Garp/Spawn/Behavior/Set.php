<?php
/**
 * @author David Spreekmeester | grrr.nl
 * @todo: REFACTOR!
 */
class Garp_Spawn_Behavior_Set {
	/**
	 * @var Array $_behaviors Associative array of Garp_Spawn_Behavior objects, where the key is the behavior name.
	 */
	protected $_behaviors = array();

	/**
	 * @var Garp_Spawn_Model_Abstract $_model
	 */
	protected $_model;

	protected $_defaultConditionalBehaviorNames = array(
		'HtmlFilterable',
		'NotEmpty',
		'Email',
		'Translatable'
	);
	
	protected $_validatorBehaviors = array(
		'Email',
		'NotEmpty'
	);


	public function __construct(Garp_Spawn_Model_Abstract $model, array $config) {
		$this->_model = $model;
		$this->_loadConfiguredBehaviors($config);
		$this->_loadDefaultConditionalBehaviors();
	}

	public function getBehaviors() {
		return $this->_behaviors;
	}
		
	public function displaysBehavior($behaviorName) {
		return array_key_exists($behaviorName, $this->_behaviors);
	}
	
	public function onAfterSingularRelationsDefinition() {
		$this->_addWeighableBehavior();
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

	protected function _add($origin, $behaviorName, $behaviorConfig = null) {
		$behaviorType = $this->_isValidatorBehavior($behaviorName) ? 'Validator' : null;
		
		if (!array_key_exists($behaviorName, $this->_behaviors)) {
			$factory 	= new Garp_Spawn_Behavior_Factory();
			$behavior 	= $factory->produce($this->_model, $origin, $behaviorName, $behaviorConfig, $behaviorType);
			$this->_behaviors[$behaviorName] = $behavior;

			////////////////////
			// if (
			// 	$behaviorName === 'Translatable' &&
			// 	$this->getModel()->id === 'Genre'
			// ) {
			// 	Zend_Debug::dump($this->getModel()->fields->getFields('origin', 'behavior'));
			// 	exit;
			// }
			////////////////////


			//	generate fields which are necessary for this behavior in the accompanying Model
			$generatedFields = $this->_behaviors[$behaviorName]->getFields();
			foreach ($generatedFields as $fieldName => $fieldParams) {
				$this->_model->fields->add('behavior', $fieldName, $fieldParams);
			}			

		} else throw new Exception("The {$behaviorName} behavior is already registered.");
	}

	protected function _isValidatorBehavior($behaviorName) {
		return in_array($behaviorName, $this->_validatorBehaviors);
	}

	protected function _loadConfiguredBehaviors(array $config) {
		foreach ($config as $behaviorName => $behaviorConfig) {
			$this->_add('config', $behaviorName, $behaviorConfig);
		}
	}

	/**
	 * Retrieves required field names. In case of a multilingual base model, the multilingual
	 * columns are not returned, since they are only required in the leaf i18n model.
	 */
	protected function _getRequiredFieldNames() {
		$model = $this->getModel();

		if (!$model->isMultilingual()) {
			return $this->_model->fields->getFieldNames('required', true);
		}

		return $this->_getUnilingualFieldNames();
	}
	
	protected function _getUnilingualFieldNames() {
		$unilingualFieldNames 	= array();
		$requiredFields 		= $this->_model->fields->getFields('required', true);
		
		foreach ($requiredFields as $field) {
			if (!$field->isMultilingual()) {
				$unilingualFieldNames[] = $field->name;
			}
		}
		
		return $unilingualFieldNames;
	}

	protected function _loadDefaultConditionalBehaviors() {
		$model 				= $this->getModel();
		$behaviorConfig 	= null;
		$behaviorType 		= null;

		foreach ($this->_defaultConditionalBehaviorNames as $behaviorName) {
			if (!$this->_needsConditionalBehavior($behaviorName)) {
				continue;
			}

			// $behaviorConfig = $this->_getConditionalBehaviorConfig($behaviorName);
			$this->_add('default', $behaviorName, $behaviorConfig, $behaviorType);
		}
	}
		
	protected function _needsConditionalBehavior($behaviorName) {
		$model = $this->getModel();
		
		switch ($behaviorName) {
			case 'HtmlFilterable':
				$htmlFieldNames = $model->fields->getFieldNames('type', 'html');
				return (bool)$htmlFieldNames;
			case 'NotEmpty':
				$requiredFieldNames = $this->_getRequiredFieldNames();
				return (bool)$requiredFieldNames;
			case 'Email':
				$emailFields = $model->fields->getFields('type', 'email');
				if (!$emailFields) {
					return false;
				}

				$emailFieldNames = array();
				foreach ($emailFields as $emailField) {
					$emailFieldNames[] = $emailField->name;
				}

				return (bool)$emailFieldNames;
			case 'Translatable':
				return $model->isMultilingual();
		}
	}

	/**
	 * Adds the weighable behavior, for user defined sorting of related objects.
	 * Can only be initialized after the relations for this model are set.
	 */
	protected function _addWeighableBehavior() {
		$model = $this->getModel();
		$weighableRels = $model->relations->getRelations('weighable', true);

		if (!$weighableRels) {
			return;
		}

		$weighableConfig = array();

		foreach ($weighableRels as $relName => $rel) {
			$weightColumn = Garp_Spawn_Util::camelcased2underscored($relName) . '_weight';
			$weighableConfig[$relName] = array(
				'foreignKeyColumn' 	=> $rel->column,
				'weightColumn' 		=> $weightColumn
			);
		}

		$this->_add('relation', 'Weighable', $weighableConfig);
	}
}