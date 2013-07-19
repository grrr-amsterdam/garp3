<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
abstract class Garp_Spawn_Behavior_Type_Abstract {
	/**
	 * @var String $_name Behavior name, f.i. 'Sluggable'
	 */
	protected $_name;

	/**
 	 * @var String
 	 */
	protected $_module;

	/**
	 * @var String $_type
	 */
	protected $_type = 'Behavior';

	/**
	 * @var String $origin Context in which this behavior is added;
	 * can be 'config' or 'default', respectively indicating the behavior
	 * was added in the model configuration file, or added by the system as a default behavior.
	 */
	protected $_origin;

	/**
	 * @var Array $params Associative array of parameters
	 */
	protected $_params;

	/**
	 * @var Array $_generatedFields
	 */
	protected $_fields = array();

	/**
	 * @var Garp_Spawn_Model_Abstract $_model
	 */
	protected $_model;
	
	protected $_validOrigins = array('default', 'config', 'relation');


	/**
	 * @param 	Garp_Spawn_Model_Abstract 	$model 			Array or StdClass object with configuration parameters for the behavior
	 * @param 	String 						$origin 		For instance: 'config'
	 * @param 	String 						$name			Behavior name, f.i. 'Sluggable'
	 * @param 	Mixed 						$params 		Array or StdClass object with configuration parameters for the behavior
	 * @param 	String 						$type 			Defaults to 'Behavior'
	 * @param   String                      $module         Defaults to 'Garp'
	 */
	public function __construct(Garp_Spawn_Model_Abstract $model, $origin, $name, $params = null, $type = null, $module = 'Garp') {
		$this->setModel($model);
		$this->setOrigin($origin);
		$this->setName($name);
		$this->setParams($params);
		$this->setType($type);
		$this->setModule($module);
	}
		
	/**
	 * @return String
	 */
	public function getName() {
		return $this->_name;
	}
	
	/**
	 * @param String $name
	 */
	public function setName($name) {
		$this->_name = $name;
	}

	/**
 	 * @return String
 	 */
	public function getModule() {
		return $this->_module;
	}

	/**
 	 * @param String $module
 	 */
	public function setModule($module) {
		$this->_module = $module;
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
		
	/**
	 * @return Array
	 */
	public function getParams() {
		return $this->_params;
	}
	
	/**
	 * @param 	Mixed	$params 	Array or object with parameters
	 */
	public function setParams($params) {
		if (is_object($params)) {
			$params = (array)$params;
		}
		
		$this->_params = $params;
	}
	
	/**
	 * @return String
	 */
	public function getOrigin() {
		return $this->_origin;
	}
	
	/**
	 * @param String $origin
	 */
	public function setOrigin($origin) {
		if (!in_array($origin, $this->_validOrigins)) {
			$validOriginsString = implode(', ', $this->_validOrigins);
			throw new Exception("'{$origin}' is not a valid origin. Try: " . $validOriginsString);
		}

		$this->_origin = $origin;
	}
		
	/**
	 * @return String
	 */
	public function getType() {
		return $this->_type;
	}
	
	/**
	 * @param String $type
	 */
	public function setType($type) {
		if ($type) {
			$this->_type = $type;
		}
	}

	/**
	 * @return Array
	 */
	public function getFields() {
		return $this->_fields;
	}
	
	/**
	 * @return 	Bool 	Whether this behavior needs to be registered with an observer
	 * 					called in the PHP model's init() method
	 */
	public function needsPhpModelObserver() {
		return true;
	}

}
