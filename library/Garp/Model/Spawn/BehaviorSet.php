<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Model_Spawn_BehaviorSet {
	/** @var Array $_behaviors Associative array of Garp_Model_Spawn_Behavior objects, where the key is the behavior name. */
	protected $_behaviors = array();

	/** @var Garp_Model_Spawn_Model */
	protected $_model;

	protected $_defaultConditionalBehaviorNames = array('HtmlFilterable', 'NotEmpty', 'Email');


	public function __construct(Garp_Model_Spawn_Model_Abstract $model, array $config) {
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

	protected function _add($origin, $behaviorName, $behaviorConfig = null, $behaviorType = null) {
		if (!array_key_exists($behaviorName, $this->_behaviors)) {
			$this->_behaviors[$behaviorName] = new Garp_Model_Spawn_Behavior($this->_model, $origin, $behaviorName, $behaviorConfig, $behaviorType);

			//	generate fields that are necessary for this behavior
			foreach ($this->_behaviors[$behaviorName]->generatedFields as $fieldName => $fieldParams) {
				$this->_model->fields->add('behavior', $fieldName, $fieldParams);
			}			
		} else throw new Exception("The {$behaviorName} behavior is already registered.");
	}


	protected function _loadConfiguredBehaviors(array $config) {
		foreach ($config as $behaviorName => $behaviorConfig) {
			$this->_add('config', $behaviorName, $behaviorConfig);
		}
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
					$requiredFieldNames = $this->_model->fields->getFieldNames('required', true);
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
					'weightColumn' => Garp_Model_Spawn_Util::camelcased2underscored($relName).'_weight'
				);
			}

			$this->_add('relation', 'Weighable', $weighableConfig);
		}
	}
}