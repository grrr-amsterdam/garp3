<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Model_Spawn_Field {
	/** Lowercase, underscored name of the field, as it appears in the database. */
	public $name;
	public $required = true;
	public $type = 'text';
	public $maxLength;
	public $label;
	public $editable = true;
	public $visible = true;
	public $default;
	public $primary = false;
	public $unique = false;
	
	const TEXTFIELD_MAX_LENGTH = 124;

	/** @var Array $options Optional values for an enum field */
	public $options = array();

	/** @var Boolean $rich Optional flag for an html field, allowing lists and media. */
	public $rich = false;

	/** @var String $origin Context in which this field is added. Can be 'config', 'default', 'relation' or 'behavior'. */
	public $origin;

	
	protected $_types = array('text', 'html', 'email', 'url', 'numeric', 'checkbox', 'datetime', 'date', 'enum');
	protected $_defaultTypeByNameEnding = array(
		'email' => 'email',
		'url' => 'url',
		'description' => 'html',
		'id' => 'numeric',
		'date' => 'date'
	);
	

	/**
	* @param String $origin Context in which this field is added. Can be 'config', 'default' or 'behavior'.
	*/
	public function __construct($origin, $name, StdClass $configParams) {
		$this->origin = $origin;
		$this->name = $name;
		$this->_loadParams($configParams);
		$this->_setConditionalDefaults($configParams);
	}
	
	
	public function isTextual() {
		switch ($this->type) {
			case 'text':
			case 'html':
			case 'email':
			case 'url':
				return true;
		}
		return false;
	}


	protected function _loadParams(StdClass $configParams) {
		foreach ($configParams as $paramName => $paramValue) {
			if (!property_exists($this, $paramName)) {
				$refl = new ReflectionObject($this);
				$reflProps = $refl->getProperties(ReflectionProperty::IS_PUBLIC);
			    $publicProps = array();
				foreach ($reflProps as $reflProp) {
					switch ($reflProp->name) {
						case 'origin':
						case 'name':
						break;
						default:
							$publicProps[] = $reflProp->name;
					}
				}
				throw new Exception("'{$paramName}' is not a valid parameter for a model field configuration. Try: ".implode($publicProps, ", "));
			} else {
				switch ($paramName) {
					case 'type':
						if (!in_array($paramValue, $this->_types))
							throw new Exception("'{$paramValue}' is not a valid field type for the '{$this->name}' field. Try: ".implode($this->_types, ", "));
					break;
					case 'options':
						if ($configParams->type === 'enum') {
							if (
								!is_array($configParams->options) ||
								!($configParams->options)
							) {
								throw new Exception("The 'options' parameter should contain an array with values.");
							}
						} else throw new Exception("The 'options' parameter is only valid for the 'enum' fields.");
					break;
				}
			}

			$this->{$paramName} = $paramValue;
		}
	}


	protected function _setConditionalDefaults(StdClass $configParams) {
		if (!property_exists($configParams, 'type')) {
			foreach ($this->_defaultTypeByNameEnding as $ending => $type) {
				if (Garp_Model_Spawn_Util::stringEndsIn($ending, $this->name)) {
					$this->type = $type;
				}
			}
		}

		if (!property_exists($configParams, 'maxLength')) {
			switch ($this->name) {
				case 'name':
				case 'subtitle':
					$this->maxLength = self::TEXTFIELD_MAX_LENGTH;
				break;
				case 'id':
					$this->maxLength = 8;
				break;
				case 'email':
					$this->maxLength = 50;
				break;
				default:
					if (Garp_Model_Spawn_Util::stringEndsIn('name', $this->name)) {
						$this->maxLength = self::TEXTFIELD_MAX_LENGTH;
					}
			}
		}

		if ($this->type === 'checkbox') {
			$this->required = false;
		}

		if (
			!property_exists($configParams, 'label') ||
			!$configParams->label
		) {
			$this->label = Garp_Model_Spawn_Util::underscored2readable($this->name);
		} else {
			$this->label = ucfirst($this->label);
		}

		$this->required = (bool)$this->required;
	}
}