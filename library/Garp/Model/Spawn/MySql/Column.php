<?php
/**
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Model
 */
class Garp_Model_Spawn_MySql_Column {
	public $position;
	public $name;
	public $type;
	public $default = null;
	public $nullable = false;
	public $length = null;
	public $unsigned = true;

	/**
	 * @var String $options Enum options
	 */
	public $options = null;

	protected $_statement;


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
	public function getDiffProperties(Garp_Model_Spawn_MySql_Column $columnToCompareWith) {
		$diffPropertyNames = array();

		$refl = new ReflectionObject($this);
		$reflProps = $refl->getProperties(ReflectionProperty::IS_PUBLIC);

		foreach ($reflProps as $reflProp) {
			$thisProp = $this->{$reflProp->name};
			$thatProp = $columnToCompareWith->{$reflProp->name};
			if (
				$reflProp->name !== 'position' &&
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
	

	/**
	 * @return String Renders a MySQL definition to use in a CREATE or ALTER statement, f.i.: col1 BIGINT UNSIGNED DEFAULT 1
	 */
	public function renderSqlDefinition() {
		$nodes = array();
		$nodes[] = '`'.$this->name.'`';

		$typeStatement = $this->type;
		if ($this->length)
			$typeStatement.= '('.$this->length.')';
		elseif ($this->type === 'enum')
			$typeStatement.= '('.$this->options.')';
		$nodes[] = $typeStatement;

		if ($this->unsigned && $this->isNumeric($this->type))
			$nodes[] = 'UNSIGNED';
			
		if (!$this->nullable)
			$nodes[] = 'NOT NULL';
		
		if ($this->default)
			$nodes[] = 'DEFAULT '.$this->quoteIfNecessary($this->default);
		elseif (is_null($this->default) && $this->nullable)
			$nodes[] = 'DEFAULT NULL';

		return implode(" ", $nodes);
	}


	static public function getFieldType(Garp_Model_Spawn_Field $field) {
		switch ($field->type) {
			case 'numeric':
				return 'int(11) unsigned';
			case 'text':
			case 'html':
				return (empty($field->maxLength) || $field->maxLength > 255) ?
					'text' :
					"varchar({$field->maxLength})"
				;
			case 'email':
			case 'url':
				return 'varchar(255)';
			case 'checkbox':
				return 'tinyint(1)';
			case 'datetime':
				return 'datetime';
			case 'date':
				return 'date';
			case 'time':
				return 'time';
			case 'enum':
				return "enum('".implode($field->options, "','")."')";
			default:
				throw new Exception("The '{$field->type}' field type can't be translated to a MySQL field type as of yet.");
		}
	}


	static public function getRequiredAndDefault(Garp_Model_Spawn_Field $field) {
		$out = array();
		if ($field->required)
			$out[] = 'NOT NULL';
		if ($field->default)
			$out[] = 'DEFAULT '.self::quoteIfNecessary($field->default);
		return implode($out, " ");
	}
	
	
	static public function quoteIfNecessary($value) {
		$decorator = null;

		if (
			!is_numeric($value) &&
			!is_bool($value) &&
			!is_null($value)
		) $decorator = "'";
		return $decorator.$value.$decorator;
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
		preg_match('/`(?P<name>\w+)` (?P<type>[\w]*)(\((?P<length>\d*)(?P<options>[^\)]*)\))? ?(?P<unsigned>UNSIGNED)? ?(?P<nullable>NOT NULL)? ?(DEFAULT \'?(?P<default>[^\',]*)\'?)?/i', trim($line), $matches);
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

		$matches['nullable'] = !(
			array_key_exists('nullable', $matches) &&
			$matches['nullable'] === 'NOT NULL'
		);

		$matches['unsigned'] = !(
			array_key_exists('unsigned', $matches) &&
			$matches['unsigned'] === 'UNSIGNED'
		);

		return $matches;
	}
}