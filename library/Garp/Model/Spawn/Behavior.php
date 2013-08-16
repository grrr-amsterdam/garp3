<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Model_Spawn_Behavior {
	public $name;
	public $type = 'Behavior';

	/** @var String $origin Context in which this behavior is added; can be 'config' or 'default', respectively indicating the behavior was added in the model configuration file, or added by the system as a default behavior. */
	public $origin;

	/** @var Array $params Associative array of parameters */
	public $params;

	/** @var Array $generatedFields */
	public $generatedFields = array();

	protected $_model;

	protected $_defaultParams = array(
		'Sluggable' => array(
			'baseField' => 'name',
			'slugField' => 'slug'
		)
	);

	protected $_generatableFields = array(
		'Timestampable' => array(
			'created' => array(
				'type' => 'datetime',
				'editable' => false
			),
			'modified' => array(
				'type' => 'datetime',
				'editable' => false
			)
		),
		'Sluggable' => array(
			':slugField' => array(
				'type' => 'text',
				'maxLength' => 255,
				'editable' => false,
				'unique' => true,
				'required' => false
			)
		),
		'Draftable' => array(
			'published' => array(
				'type' => 'datetime',
				'editable' => true,
				'required' => false
			),
			'online_status' => array(
				'type' => 'checkbox',
				'editable' => true,
				'default' => 1,
				'required' => false
			)
		),
		'Locatable' => array(
			'location_lat' => array(
				'type' => 'numeric',
				'float' => true,
				'required' => false,
				'editable' => true,
				'visible' => false
			),
			'location_long' => array(
				'type' => 'numeric',
				'float' => true,
				'required' => false,
				'editable' => true,
				'visible' => false
			),
		)
	);
	
	protected $_validOrigins = array('default', 'config', 'relation');


	/**
	 * @param Mixed $params Array or StdClass object with configuration parameters for the behavior
	 */
	public function __construct(Garp_Model_Spawn_Model $model, $origin, $name, $params = null, $behaviorType = null) {
		$this->name = $name;
		$this->_model = $model;

		if (is_object($params))
			$params = (array)$params;
		$this->params = $this->_appendDefaultParams($params);

		if (in_array($origin, $this->_validOrigins))
			$this->origin = $origin;
		else throw new Exception("'".$origin."' is not a valid origin. Try: ".implode(", ", $this->_validOrigins));

		if ($behaviorType)
			$this->type = $behaviorType;

		$this->_setGeneratedFields();
	}
	
	
	protected function _appendDefaultParams($params) {
		if (array_key_exists($this->name, $this->_defaultParams)) {
			$configIsProvided = !(is_null($params) || empty($params));

			if (!$configIsProvided) {
				$params = $this->_defaultParams[$this->name];
			} else {
				foreach ($this->_defaultParams[$this->name] as $defaultParamName => $defaultParamValue) {
					if (!array_key_exists($defaultParamName, $params)) {
						$params[$defaultParamName] = $defaultParamValue;
					}
				}
			}
		}
		
		return $params;
	}


	protected function _setGeneratedFields() {
		if (array_key_exists($this->name, $this->_generatableFields)) {
			foreach ($this->_generatableFields[$this->name] as $fieldName => $fieldValue) {
				if (substr($fieldName, 0, 1) === ':') {
					$dynamicFieldName = substr($fieldName, 1);
					if (array_key_exists($dynamicFieldName, $this->params)) {
						$this->generatedFields[$this->params[$dynamicFieldName]] = $fieldValue;
					} else {
						throw new Exception("The parameter '{$dynamicFieldName}' was not found, but is needed to set the generated field for the {$this->name} behavior.");
					}
				} else $this->generatedFields[$fieldName] = $fieldValue;
			}
		}
	}
}