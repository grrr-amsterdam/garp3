<?php
/**
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Model
 */
class Garp_Spawn_MySql_Column {
	public $position;
	public $name;
	public $type;
	public $default 		= null;
	public $nullable 		= false;
	public $length 			= null;
	public $decimals 		= null;
	public $unsigned 		= true;
	public $auto_increment 	= false;

	/** @var String $options Enum options */
	public $options = null;

	protected $_statement;
	
	protected $_ignorableDiffProperties = array('position');
	
	protected $_columnOptions = array(
		'not_nullable' 		=> 'NOT NULL',
		'unsigned'			=> 'UNSIGNED',
		'auto_increment'	=> 'AUTO_INCREMENT'
	);


	/**
	* 	
	 * @param String $line Line with a column definition statement in SQL
	 */
	public function __construct($position, $line) {
		$this->position = $position;
		$params = $this->_parseColumnStatement($line);

		foreach ($params as $pName => $pValue) {
			if (property_exists($this, $pName)) {
				$this->{$pName} = $pValue;
			} else {
				$refl = new ReflectionObject($this);
				$reflProps = $refl->getProperties(ReflectionProperty::IS_PUBLIC);
			    $publicProps = array();
				foreach ($reflProps as $reflProp) {
					if ($reflProp->name !== 'position')
							$publicProps[] = $reflProp->name;
				}
				throw new Exception("'{$pName}' is not a valid column property. Try: ".implode($publicProps, ", "));
			}
		}
	}


	/**
	 * @return Array Numeric array containing the names of the properties that are different, compared to the provided column
	 */
	public function getDiffProperties(Garp_Spawn_MySql_Column $columnToCompareWith) {
		$diffPropertyNames = array();

		$refl = new ReflectionObject($this);
		$reflProps = $refl->getProperties(ReflectionProperty::IS_PUBLIC);

		foreach ($reflProps as $reflProp) {
			$thisProp = $this->{$reflProp->name};
			$thatProp = $columnToCompareWith->{$reflProp->name};
			
			if (
				!$this->_isIgnorableDiffProperty($reflProp->name) &&
				(
					(
						$reflProp->name === 'default' &&
						$thisProp !== $thatProp
					) ||
					$thisProp != $thatProp
				)
			) {
				$diffPropertyNames[] = $reflProp->name;
			}
		}

		return $diffPropertyNames;
	}
	
	protected function _isIgnorableDiffProperty($propName) {
		return in_array($propName, $this->_ignorableDiffProperties);
	}

	/**
	 * @return String 	Renders a MySQL definition to use in a CREATE or ALTER statement,
	 * 					f.i.: col1 BIGINT UNSIGNED DEFAULT 1
	 */
	public function renderSqlDefinition() {
		$nodes = array();
		$nodes[] = '`'.$this->name.'`';

		$typeStatement = $this->type;
		if ($this->length || $this->decimals) {
			$typeStatement.= '('
				.($this->length ?: $this->decimals)
				.($this->decimals ? (','.$this->decimals) : '')
			.')';
		} elseif ($this->type === 'enum') {
			$typeStatement.= '('.$this->options.')';
		}
		$nodes[] = $typeStatement;

		if ($this->unsigned && $this->isNumeric($this->type)) {
			$nodes[] = 'UNSIGNED';
		}
			
		if (!$this->nullable) {
			$nodes[] = 'NOT NULL';
		}

		if ($this->auto_increment) {
			$nodes[] = 'AUTO_INCREMENT';
		}

		if (isset($this->default)) {
			$default = $this->default;
			
			$default = $this->_convertDefaultIfNecessary($default);
			$default = $this->quoteIfNecessary($this->type, $default);
			$nodes[] = 'DEFAULT ' . $default;
		} elseif (is_null($this->default) && $this->nullable) {
			$nodes[] = 'DEFAULT NULL';
		}

		return implode(" ", $nodes);
	}


	static public function renderFieldSql(Garp_Spawn_Field $field) {
		$type = Garp_Spawn_MySql_Column::getFieldType($field);
		$reqAndDef = Garp_Spawn_MySql_Column::getRequiredAndDefault($field);
		if ($reqAndDef)
			$reqAndDef = ' '.$reqAndDef;
		$autoIncr = $field->name === 'id' ?	' AUTO_INCREMENT' : '';
		return "  `{$field->name}` {$type}{$reqAndDef}{$autoIncr}";
	}


	static public function getFieldType(Garp_Spawn_Field $field) {
		switch ($field->type) {
			case 'numeric':
				if ($field->float) {
					$type = 'double(19,16)';
				} else {
					$type = 'int(11)';
				}
				
				if ($field->unsigned) {
					$type .= ' UNSIGNED';
				}
				return $type;
				
			case 'text':
			case 'html':
				if (empty($field->maxLength) || $field->maxLength > 255) {
					return 'text';
				}
				if ($field->maxLength <= 10) {
					return "char({$field->maxLength})";
				}
				return "varchar({$field->maxLength})";

			case 'email':
			case 'url':
			case 'document':
			case 'imagefile':
				return 'varchar(255)';

			case 'checkbox':
				return 'tinyint(1)';

			case 'datetime':
			case 'date':
			case 'time':
				return $field->type;

			case 'enum':
				$options = $field->options;
				if (is_object($options) || (is_array($options) && key($options) !== 0)) {
					//	this enum field has labels attached to it, but only the values are stored in the database.
					$options = array_keys((array)$options);
				}
				return "enum('".implode($options, "','")."')";

			default:
				throw new Exception("The '{$field->type}' field type can't be translated to a MySQL field type as of yet.");
		}
	}

	static public function getRequiredAndDefault(Garp_Spawn_Field $field) {
		$out = array();
		if ($field->required) {
			$out[] = 'NOT NULL';
		}
		
		if (isset($field->default)) {
			$default = $field->default;
			$default = self::convertDefaultIfNecessary($default, $field);
			$default = self::quoteIfNecessary($field->type, $default);
			$out[] = 'DEFAULT ' . $default;
		}

		return implode($out, " ");
	}
	
	
	static public function quoteIfNecessary($fieldType, $value) {
		$decorator = null;

		if (
			$fieldType === 'enum' ||
			(
				!is_numeric($value) &&
				!is_bool($value) &&
				!is_null($value)
			)
		) $decorator = "'";
		return $decorator.$value.$decorator;
	}

	/**
	 * @todo: Deze onderstaande functies samenvoegen, en sowieso alle statische functies eruit schrijven.
	 */
	static public function convertDefaultIfNecessary($default, Garp_Spawn_Field $field) {
		if (! (is_bool($default) && !$default)) {
			return $default;
		}

		if ($field->type !== 'checkbox') {
			throw new Exception("Field {$field->name} (type: {$field->type}) cannot have false (boolean) as default.");
		}

		return 0;
	}

	protected function _convertDefaultIfNecessary($default) {
		if (! (is_bool($default) &&	!$default)) {
			return $default;
		}

		if ($this->type !== 'tinyint(1)') {
			throw new Exception("A {$this->type} column cannot have false (boolean) as default.");
		}

		return 0;
	}
	

	static public function isNumeric($sqlType) {
		switch ($sqlType) {
			case 'numeric':
			case 'timestamp':
			case 'bigint':
			case 'tinyint':
			case 'int':
			case 'float':
				return true;
		}
		return false;
	}


	protected function _parseColumnStatement($line) {
		$matches = array();
		$pattern = '/`(?P<name>\w+)` (?P<type>[\w]*)(\(((?P<length>\d*),?\s*(?P<decimals>\d*))(?P<options>[^\)]*)\))? ?(?P<unsigned>UNSIGNED)? ?(?P<not_nullable>NOT NULL)? ?(?P<auto_increment>AUTO_INCREMENT)? ?(DEFAULT \'?(?P<default>[^\',]*)\'?)?/i';
		preg_match($pattern, trim($line), $matches);
		$matches['_statement'] = $matches[0];

		foreach ($matches as $key => $match) {
			if (is_numeric($key))
				unset($matches[$key]);
		}
		
		if (array_key_exists('default', $matches)) {
			if ($matches['default'] === 'NULL')
				$matches['default'] = null;
			elseif ($this->isNumeric($matches['type'])) {
				if (strpos($matches['default'], '.') !== false)
					$matches['default'] = (float)$matches['default'];
				else $matches['default'] = (int)$matches['default'];
			}
		}

		$matches['nullable'] = !$this->_propExistsAndMatches($matches, 'not_nullable');
		unset($matches['not_nullable']);

		$matches['unsigned'] = $this->_propExistsAndMatches($matches, 'unsigned');
		$matches['auto_increment'] = $this->_propExistsAndMatches($matches, 'auto_increment');

		return $matches;
	}
	
	/**
	 * @param	Array	$matches	Result of preg_match on sql statement
	 * @param	String	$prop		Matched property in sql statement, i.e. nullable | unsigned | auto_increment
	 */
	protected function _propExistsAndMatches(array $matches, $prop) {
		$existsAndMatches = 
			array_key_exists($prop, $matches) &&
			strcasecmp($matches[$prop], $this->_columnOptions[$prop]) === 0
		;
		
		return $existsAndMatches;
	}
}
