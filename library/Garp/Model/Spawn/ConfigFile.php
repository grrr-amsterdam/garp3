<?php
/**
 * Configuration file for the model to be spawned
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Model
 * @lastmodified $Date: $
 */
class Garp_Model_Spawn_ConfigFile {
	protected $_userConfig;

	const _MODEL_CONFIG_PATH = '/modules/default/models/config/';
	const _HABTM_FILE = '_HabtmRelations.json';
	protected $_allowedConfigFields = array('order', 'label', 'listFields', 'route', 'creatable', 'deletable', 'quickAddable', 'inputs', 'behaviors', 'relations', 'module', 'visible');


	/**
	 * Class constructor
	 * @return Void
	 */
	public function __construct($modelId) {
		$this->id = $modelId;

		$config = $this->_loadModelFile();
		$this->_userConfig = $config;
	}


	/**
	* @return Array Returns an array of configurated model ID's (capitalized and camelcased names) of which there is a configuration file present.
	**/
	public static function findAll() {
		$models = array();

		if ($handle = opendir(self::_getModelsConfigPath())) {
			while (false !== ($entry = readdir($handle))) {
				if (self::_isModelConfigFile($entry)) {
					$models[] = self::_getModelFromConfigPath($entry);
				}
			}
		} else throw new Exception('Unable to open the model base directory at '.self::getModelsBasePath());

		return $models;
	}
	
	
	public static function loadHabtm() {
		$path = self::_getModelsConfigPath().self::_HABTM_FILE;
		$config = self::_loadFile($path);
		self::_validateHabtmFile($config);
		return $config;
	}


	public function getLabel() {
		if (property_exists($this->_userConfig, 'label'))
			return $this->_userConfig->label;
	}

	public function getOrder() {
		if (property_exists($this->_userConfig, 'order'))
			return $this->_userConfig->order;
	}
	
	public function getRoute() {
		if (property_exists($this->_userConfig, 'route'))
			return $this->_userConfig->route;
	}

	public function getModule() {
		if (property_exists($this->_userConfig, 'module'))
			return lcfirst($this->_userConfig->module);
	}

	public function getInputs() {
		if (property_exists($this->_userConfig, 'inputs'))
			return $this->_userConfig->inputs;
	}
	
	public function getBehaviors() {
		if (property_exists($this->_userConfig, 'behaviors'))
			return $this->_userConfig->behaviors;
		else return new StdClass();
	}

	public function getRelations() {
		if (property_exists($this->_userConfig, 'relations'))
			return $this->_userConfig->relations;
		else return new StdClass();
	}

	public function getListFields() {
		if (property_exists($this->_userConfig, 'listFields'))
			return (array)$this->_userConfig->listFields;
		else return array();
	}

	public function getCreatable() {
		if (property_exists($this->_userConfig, 'creatable'))
			return $this->_userConfig->creatable;
	}

	public function getDeletable() {
		if (property_exists($this->_userConfig, 'deletable'))
			return $this->_userConfig->deletable;
	}

	public function getQuickAddable() {
		if (property_exists($this->_userConfig, 'quickAddable'))
			return $this->_userConfig->quickAddable;
	}

	public function getVisible() {
		if (property_exists($this->_userConfig, 'visible'))
			return $this->_userConfig->visible;
	}


	
	
	/**
	 * @return String The absolute path to the model configuration files
	 */
	protected static function _getModelsConfigPath() {
		return APPLICATION_PATH.self::_MODEL_CONFIG_PATH;
	}


	protected static function _getModelFromConfigPath($path) {
		return substr($path, 0, -5);
	}
	
	
	protected static function _isModelConfigFile($file) {
		$path = self::_getModelsConfigPath().$file;

		return 
			is_file($path) &&
			substr(basename($path), -5, 5) === '.json' &&
			basename($path) !== self::_HABTM_FILE
		;
	}


	/**
	* @param 	String 	$model 	The model's id
	* @return 	StdClass		Validated model configuration
	**/
	protected function _loadModelFile() {
		$configFilePath = $this->_getModelsConfigPath().$this->id.'.json';
		$config = $this->_loadFile($configFilePath);
		$this->_validateModelFile($config);
		return $config;
	}


	static protected function _loadFile($configFilePath) {
		if (!file_exists($configFilePath))
			throw new Exception("The configuration file does not exist yet. Create one at ".$configFilePath);

		$configJson = file_get_contents($configFilePath);

		if (!strlen($configJson))
			throw new Exception("The configuration file is empty. Fill it at ".$configFilePath);
		
		$config = json_decode($configJson);		

		if (is_null($config))
			throw new Exception("The configuration file contains invalid JSON. Correct it at {$configFilePath} - don't forget the double quotes around array keys and string-like values.");

		return $config;
	}


	protected function _validateModelFile(StdClass $config) {
		foreach ($config as $configFieldName => $configFieldValue) {
			if (!in_array($configFieldName, $this->_allowedConfigFields)) {
				throw new Exception("'{$configFieldName}' is not a valid config field. Try: '".implode("', '", $this->_allowedConfigFields)."'");
			}
		}

		if (property_exists($config, 'id'))
			throw new Exception("The 'id' property cannot be defined in the {$config->id} model configuration file, but is derived from the model directory name instead.");

		if (!property_exists($config, 'inputs'))
			throw new Exception("The 'inputs' property was not defined in the model configuration for ".$config->id);
		
		if (property_exists($config, 'relations')) {
			foreach ($config->relations as $relationName => $relation) {
				if (!property_exists($relation, 'type'))
					throw new Exception("The {$relationName} relation in the {$this->id} model configuration lacks the 'type' property (should be hasOne, belongsTo, hasMany or hasAndBelongsToMany).");
				elseif (
					$relation->type !== 'hasOne' &&
					$relation->type !== 'belongsTo'
				) throw new Exception ("Only singular (hasOne, belongsTo) relations can be defined in the model's configuration. A hasMany relation from local to remote should be defined as a singular relation from the remote model to the local model. A hasAndBelongsToMany relation should be defined in the separate hasAndBelongsToMany configuration file.");
			}
		}

		if (
			property_exists($config, 'module') &&
			$config->module !== 'garp'
		) throw new Exception("'{$config->module}' is an invalid module name. Please use 'garp' instead, or do not set the module field at all, to leave it at the default (application) module.");
			
	}


	static protected function _validateHabtmFile($config) {
		if (!is_array($config))
			throw new Exception("The configuration for hasAndBelongsToMany relations should consist of an array, containing binding model names, such as 'Posts_Users'.");

		foreach ($config as $bindingModelName) {
			$modelNames = explode('_', $bindingModelName);

			if (
				strpos($bindingModelName, "_") === false ||
				count($modelNames) !== 2
			) throw new Exception("Binding model names should sequence the two bindable models in alphabetic order, separated by an underscore. For instance: 'Posts_Users'.");

			$alphabeticBindingModelName = Garp_Model_Spawn_Relations::getBindingModelName($modelNames[0], $modelNames[1]);

			if (str_replace('_', '', $bindingModelName) !== $alphabeticBindingModelName)
				throw new Exception("Binding model names should sequence the two bindable models in alphabetic order; '{$alphabeticBindingModelName}', not '{$bindingModelName}'.");
		}
	}
}