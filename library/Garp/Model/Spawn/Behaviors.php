<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Model_Spawn_Behaviors {
	/** @var Array $_behaviors Associative array of Garp_Model_Spawn_Behavior objects, where the key is the behavior name. */
	protected $_behaviors = array();

	/** @var Garp_Model_Spawn_Model */
	protected $_model;
	
	protected $_defaultBehaviorNames = array('Timestampable', 'Authorable');
	protected $_defaultConditionalBehaviorNames = array('HtmlFilterable', 'NotEmpty', 'Email');


	public function __construct(Garp_Model_Spawn_Model $model, StdClass $config) {
		$this->_model = $model;
		$this->_loadConfiguredBehaviors($config);
		$this->_loadDefaultBehaviors();
		$this->_loadDefaultConditionalBehaviors();
	}


	public function getBehaviors() {
		return $this->_behaviors;
	}
	
	
	public function displaysBehavior($behaviorName) {
		return array_key_exists($behaviorName, $this->_behaviors);
	}


	protected function _add($origin, $behaviorName, $behaviorConfig = null, $behaviorType = null) {
		if (!array_key_exists($behaviorName, $this->_behaviors)) {
			$this->_behaviors[$behaviorName] = new Garp_Model_Spawn_Behavior($this->_model, $origin, $behaviorName, $behaviorConfig, $behaviorType);

			//	generate fields that are necessary for this behavior
			foreach ($this->_behaviors[$behaviorName]->generatedFields as $fieldName => $fieldParams) {
				$this->_model->fields->add('behavior', $fieldName, (object)$fieldParams);
			}			
		} else throw new Exception("The {$behaviorName} behavior is already registered.");
	}


	protected function _loadConfiguredBehaviors(StdClass $config) {
		foreach ($config as $behaviorName => $behaviorConfig) {
			$this->_add('config', $behaviorName, $behaviorConfig);
		}
	}
	
	
	protected function _loadDefaultBehaviors() {
		$behaviorConfig = null;

		foreach ($this->_defaultBehaviorNames as $behaviorName) {
			$this->_add('default', $behaviorName, $behaviorConfig);
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
						$requiredEmailFields = array();
						foreach ($emailFields as $emailField) {
							if ($emailField->required)
								$requiredEmailFields[] = $emailField->name;
						}

						if ($requiredEmailFields) {
							$behaviorType = 'Validator';
							$behaviorConfig = $requiredEmailFields;
							$this->_add('default', $behaviorName, $behaviorConfig, $behaviorType);
						}
					}
				break;
				default:
					$this->_add('default', $behaviorName, $behaviorConfig, $behaviorType);
			}
		}
	}
}