<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Spawn_Field {
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
	public $info;
	public $index;
	public $multilingual = false;

	
	const TEXTFIELD_MAX_LENGTH = 124;

	/** @var Array $options Optional values for an enum field */
	public $options = array();

	/** @var Boolean $float Whether this is a floating point value, in case of a numeric field. */
	public $float = false;

	/** @var Boolean $unsigned Whether this is an unsigned value, in case of a numeric field. */
	public $unsigned = true;

	/** @var Boolean $rich Optional flag for an html field, allowing lists and media. */
	public $rich = false;

	/** @var String $origin Context in which this field is added. Can be 'config', 'default', 'relation' or 'behavior'. */
	public $origin;

	
	protected $_types = array('text', 'html', 'email', 'url', 'numeric', 'checkbox', 'datetime', 'date', 'time', 'enum', 'document', 'imagefile');
	protected $_defaultTypeByNameEnding = array(
		'email' => 'email',
		'url' => 'url',
		'description' => 'html',
		'id' => 'numeric',
		'date' => 'date',
		'time' => 'time'
	);


	/**
	* @param String $origin Context in which this field is added. Can be 'config', 'default' or 'behavior'.
	*/
	public function __construct($origin, $name, array $config) {
		$this->origin = $origin;
		$this->name = $name;
		$this->_loadParams($config);
		$this->_setConditionalDefaults($config);
	}


	public function isTextual() {
		$textualTypes = array('text', 'html', 'email', 'url', 'document');
		return in_array($this->type, $textualTypes);
	}

	public function isMultilingual() {
		return $this->multilingual;
	}
	
	public function isRelationField() {
		return $this->origin === 'relation';
	}
	
	public function isSuitableAsLabel() {
		$nonLabelFieldTypes 	= array('html', 'checkbox');
		$isSuitableType 		= !in_array($this->type, $nonLabelFieldTypes);
		$isSuitableField		= $isSuitableType && !$this->isRelationField();
		
		return $isSuitableField;
	}

	protected function _loadParams(array $config) {
		foreach ($config as $paramName => $paramValue) {
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
				throw new Exception("'{$paramName}' is not a valid parameter for the '{$this->name}' field configuration. Try: ".implode($publicProps, ", "));
			} else {
				switch ($paramName) {
					case 'type':
						if (!in_array($paramValue, $this->_types))
							throw new Exception("'{$paramValue}' is not a valid field type for the '{$this->name}' field. Try: ".implode($this->_types, ", "));
					break;
					case 'options':
						if ($config['type'] === 'enum') {
							if (
								(!is_array($config['options']) && !is_object($config['options'])) ||
								!($config['options'])
							) {
								throw new Exception("The 'options' parameter should contain an array with db enum values, or an object with db enum values as object keys, and labels as object values.");
							}
						} else throw new Exception("The 'options' parameter is only valid for the 'enum' fields.");
					break;
				}
			}

			$this->{$paramName} = $paramValue;
		}
	}


	protected function _setConditionalDefaults(array $config) {
		if (!array_key_exists('type', $config)) {
			foreach ($this->_defaultTypeByNameEnding as $ending => $type) {
				if (Garp_Spawn_Util::stringEndsIn($ending, $this->name)) {
					$this->type = $type;
				}
			}
		}

		if (!array_key_exists('maxLength', $config)) {
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
					if (Garp_Spawn_Util::stringEndsIn('name', $this->name)) {
						$this->maxLength = self::TEXTFIELD_MAX_LENGTH;
					}
			}
		}

		if ($this->type === 'checkbox') {
			$this->required = false;
		}

		if (
			!array_key_exists('label', $config) ||
			!$config['label']
		) {
			$this->label = Garp_Spawn_Util::underscored2readable(
				Garp_Spawn_Util::stringEndsIn('_id', $this->name) ?
					substr($this->name, 0, -3) :
					$this->name
			);
		} else {
			$this->label = ucfirst($this->label);
		}

		$this->required = (bool)$this->required;
	}
}