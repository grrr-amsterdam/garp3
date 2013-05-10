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

	protected $_defaultConditionalBehaviorNames = array('HtmlFilterable', 'NotEmpty', 'Email');


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

	protected function _add($origin, $behaviorName, $behaviorConfig = null, $behaviorType = null) {
		if (!array_key_exists($behaviorName, $this->_behaviors)) {
			$factory 	= new Garp_Spawn_Behavior_Factory();
			$behavior 	= $factory->produce($this->_model, $origin, $behaviorName, $behaviorConfig, $behaviorType);
			$this->_behaviors[$behaviorName] = $behavior;

			//	generate fields which are necessary for this behavior in the accompanying Model
			$generatedFields = $this->_behaviors[$behaviorName]->getFields();
			foreach ($generatedFields as $fieldName => $fieldParams) {
				$this->_model->fields->add('behavior', $fieldName, $fieldParams);
			}			
		} else throw new Exception("The {$behaviorName} behavior is already registered.");
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
		$behaviorConfig = null;
		$behaviorType = null;

		foreach ($this->_defaultConditionalBehaviorNames as $behaviorName) {
			switch ($behaviorName) {
				case 'HtmlFilterable':
					$htmlFieldNames = $this->_model->fields->getFieldNames('type', 'html');
					if ($htmlFieldNames) {
						$behaviorConfig = $htmlFieldNames;
						$this->_add('default', $behaviorName, $behaviorConfig, $behaviorType);
					}
				break;
				case 'NotEmpty':
					$requiredFieldNames = $this->_getRequiredFieldNames();
					if ($requiredFieldNames) {
						$behaviorType = 'Validator';
						$behaviorConfig = $requiredFieldNames;
						$indexOfIdColumn = array_search('id', $behaviorConfig);
						unset($behaviorConfig[$indexOfIdColumn]);
						$this->_add('default', $behaviorName, $behaviorConfig, $behaviorType);
					}
				break;
				case 'Email':
					$emailFields = $this->_model->fields->getFields('type', 'email');
					if ($emailFields) {
						$validatableEmailFields = array();

						foreach ($emailFields as $emailField) {
							$validatableEmailFields[] = $emailField->name;
						}

						if ($validatableEmailFields) {
							$behaviorType = 'Validator';
							$behaviorConfig = $validatableEmailFields;
							$this->_add('default', $behaviorName, $behaviorConfig, $behaviorType);
						}
					}
				break;
				default:
					$this->_add('default', $behaviorName, $behaviorConfig, $behaviorType);
			}
		}
	}
	

	/**
	 * Adds the weighable behavior, for user defined sorting of related objects. Can only be initialized after the relations for this model are set.
	 */
	protected function _addWeighableBehavior() {
		$weighableRels = $this->_model->relations->getRelations('weighable', true);

		if ($weighableRels) {
			$weighableConfig = array();

			foreach ($weighableRels as $relName => $rel) {
				$weighableConfig[$relName] = array(
					'foreignKeyColumn' => $rel->column,
					'weightColumn' => Garp_Spawn_Util::camelcased2underscored($relName).'_weight'
				);
			}

			$this->_add('relation', 'Weighable', $weighableConfig);
		}
	}
}