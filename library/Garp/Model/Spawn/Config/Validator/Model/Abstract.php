<?php
/**
 * Validation for a model configuration.
 * @author David Spreekmeester | grrr.nl
 */
abstract class Garp_Model_Spawn_Config_Validator_Model_Abstract implements Garp_Model_Spawn_Config_Validator_Interface {
	protected $_mandatoryProps = array('id');

	protected $_valueRestrictedProps = array(
		'module' => array('default', 'garp')
	);

	protected $_valueTypeRestrictedProps = array(
		'order' => array(
			'type' => 'string',
			'message' => "The 'order' attribute in the model configuration should be a string. To sort by multiple columns, use comma seperated SQL syntax, i.e.: 'name ASC, created DESC'."
		)
	);
	
	protected $_fieldTypeRestrictions = array(
		'checkbox' => array(
			'required' => true
		)
	);


	/* Translations from internal model property (key) to configuration field (value).
	 * i.e.: Since not all model fields can be configurated directly,
	 * the 'fields' of a model are defined in config files as 'inputs'. */	
	protected $_translatedProperties = array('fields' => 'inputs');

	protected $_configurableRelationTypes = array('hasOne', 'belongsTo', 'hasAndBelongsToMany');

	protected $_defaultRelationType = 'hasOne';
	
	protected $_configurablePropertiesOutsideOfModel = array('listFields');


	public function validate(ArrayObject $config) {
		$this->_requireMandatoryProps($config);
		$this->_validatePropsAreAllowed($config);
		$this->_validateValueRestrictedProps($config);
		$this->_validateValueTypeRestrictedProps($config);
		$this->_validateFieldTypeRestrictedProps($config);
		$this->_validateRelationTypes($config);
		
		// specific and / or complex logic:
		$this->_validateUniqueKeyLength($config);
		$this->_validateIdCharacters($config);
	}


	protected function _getAllowedConfigProps() {
		$modelClass = new ReflectionClass('Garp_Model_Spawn_Model');
		$getName = function($value) { return $value->name; };
		$modelProps = array_map($getName, $modelClass->getProperties(ReflectionProperty::IS_PUBLIC));

		foreach ($this->_translatedProperties as $fromProp => $toProp) {
			unset($modelProps[array_search($fromProp, $modelProps)]);
			$modelProps[] = $toProp;			
		}
		
		$modelProps = array_merge($modelProps, $this->_configurablePropertiesOutsideOfModel);

		return $modelProps;
	}


	protected function _requireMandatoryProps(ArrayObject $config) {
		foreach ($this->_mandatoryProps as $p) {
			if (!array_key_exists($p, $config))
				throw new Exception("The '{$p}' property was not defined in the model configuration for ".$config['id']);
		}
	}


	protected function _validatePropsAreAllowed(ArrayObject $config) {
		$allowedProps = $this->_getAllowedConfigProps();

		foreach ($config as $configFieldName => $configFieldValue) {
			if (!in_array($configFieldName, $allowedProps)) {
				throw new Exception("'{$configFieldName}' is not a valid config field. Try: '".implode("', '", $allowedProps)."'");
			}
		}
	}
	
	
	protected function _validateValueRestrictedProps(ArrayObject $config) {
		foreach ($this->_valueRestrictedProps as $prop => $validValues) {
			if (array_key_exists($prop, $config)) {
				if (!in_array($config[$prop], $validValues)) {
					throw new Exception("'{$this[$prop]}' is not a valid value for the {$prop} configuration field. Try: '".implode("', '", $validValues)."'");
				}
			}
		}
	}


	protected function _validateValueTypeRestrictedProps(ArrayObject $config) {
		foreach ($this->_valueTypeRestrictedProps as $propName => $propSetting) {
			$mandatoryType = $propSetting['type'];
			if (array_key_exists($propName, $config)) {
				$typeCheckFunction = 'is_'.$mandatoryType;
				if (!$typeCheckFunction($config[$propName])) {
					$currentType = gettype($config[$propName]);
					if (
						array_key_exists('message', $propSetting) &&
						$propSetting['message']
					) {
						throw new Exception($propSetting['message']);
					} else throw new Exception("The provided '{$propName}' configuration field is of type {$currentType}, but should be of type {$mandatoryType}.");
				}
			}
		}
	}
	
	
	protected function _validateFieldTypeRestrictedProps(ArrayObject $config) {
		foreach ($config['inputs'] as $fieldName => $fieldConfig) {
			if (array_key_exists('type', $fieldConfig)) {
				foreach ($this->_fieldTypeRestrictions as $fieldType => $restrictions) {
					if ($fieldType === $fieldConfig['type']) {
						foreach ($restrictions as $paramName => $paramValue) {
							if (
								array_key_exists($paramName, $fieldConfig) &&
								$fieldConfig[$paramName] !== $paramValue
							) {
								$requiredValue = is_bool($paramValue) ?
									($paramValue ? 'true' : 'false') :
									$paramValue
								;
								$requiredValueType = gettype($paramValue);
								throw new Exception("Error in configuration of {$config['id']}.{$fieldName}: For inputs of type '{$fieldType}', the '{$paramName}' property is only allowed to be {$requiredValue}, of type {$requiredValueType}.");
							}
						}
					}
				}
			}
		}
	}


	protected function _validateRelationTypes(ArrayObject $config) {
		if (array_key_exists('relations', $config)) {
			foreach ($config['relations'] as $relationName => &$relation) {
				if (!array_key_exists('type', $relation)) {
					$relation['type'] = $this->_defaultRelationType;
				} elseif (!in_array($relation['type'], $this->_configurableRelationTypes)) {
					$configurableRelationTypesList = "'".implode("', '", $this->_configurableRelationTypes)."'";

					throw new Exception (
						"Only {$configurableRelationTypesList} relations can be defined in the model's configuration, not {$relation['type']}. A hasMany relation from local to remote should be defined as a singular relation from the remote model to the local model. A hasAndBelongsToMany relation should be defined in the separate hasAndBelongsToMany configuration file."
						. " Relation: {$config['id']} <-> {$relationName}"
					);
				}
				if (
					$relation['type'] === 'hasOne' &&
					array_key_exists('required', $relation) &&
					$relation['required']
				) {
					throw new Exception("Sorry to be a smartass, but you've configured a relation you shouldn't want. {$config['id']} <-> {$relationName} is hasOne AND required, but this would result in conflicting behavior. 'hasOne' relations cannot be required, since that could possibly result in corrupt data.");
				}
			}
		}
	}


	/**
	 * Throw a warning when a field is set to unique, while its maxLength is too large.
	 */
	protected function _validateUniqueKeyLength(ArrayObject $config) {
		$restrictedFieldTypes = array('text', 'html');

		if (array_key_exists('inputs', $config)) {
			foreach ($config['inputs'] as $inputName => $input) {
				if (
					array_key_exists('unique', $input) &&
					$input['unique'] &&
					(
						(
							!array_key_exists('type', $input) &&
							!Garp_Model_Spawn_Util::stringEndsIn('email', $inputName) &&
							!Garp_Model_Spawn_Util::stringEndsIn('url', $inputName) &&
							!Garp_Model_Spawn_Util::stringEndsIn('id', $inputName) &&
							!Garp_Model_Spawn_Util::stringEndsIn('date', $inputName) &&
							!Garp_Model_Spawn_Util::stringEndsIn('time', $inputName)
						) ||
						(
							array_key_exists('type', $input) &&
							in_array($input['type'], $restrictedFieldTypes)
						)
					) &&
					(
						!array_key_exists('maxLength', $input) ||
						$input['maxLength'] > 255
					)
				) {
					throw new Exception("You've set {$config['id']}.{$inputName} to unique, but this type of field has to have a maxLength of 255 at most to be made unique.");
				}
			}
		}
	}
	
	
	protected function _validateIdCharacters(ArrayObject $config) {
		if (!ctype_alnum($config['id'])) {
			throw new Exception("Your model name '{$config['id']}' should only consist of alphanumeric characters.");
		}
	}
}