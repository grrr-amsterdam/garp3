<?php
/**
 * Garp_Spawn_Db_Column
 *
 * @package Garp_Spawn_Db
 * @author  David Spreekmeester <david@grrr.nl>
 */
abstract class Garp_Spawn_Db_Column {
    public $position;
    public $name;
    public $type;
    public $default         = null;
    public $nullable        = false;
    public $length          = null;
    public $decimals        = null;
    public $unsigned        = true;
    public $auto_increment  = false;

    /**
     * @var string $options Enum
     */
    public $options = null;

    protected $_statement;

    protected $_ignorableDiffProperties = ['position'];

    protected $_columnOptions = [
        'not_nullable'   => 'NOT NULL',
        'unsigned'       => 'UNSIGNED',
        'auto_increment' => 'AUTO_INCREMENT'
    ];

    /**
     * @var Garp_Spawn_Db_Schema_Interface
     */
    protected $_schema;


    /**
     * @param Garp_Spawn_Db_Schema_Interface $schema
     * @param int $position
     * @param string $line Line with a column definition statement in SQL
     * @return void
     */
    public function __construct(Garp_Spawn_Db_Schema_Interface $schema, $position, $line) {
        $this->_schema = $schema;
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
                    if ($reflProp->name !== 'position') {
                        $publicProps[] = $reflProp->name;
                    }
                }
                throw new Exception(
                    "'{$pName}' is not a valid column property. Try: " . implode($publicProps, ", ")
                );
            }
        }
    }


    /**
     * @param Garp_Spawn_Db_Column $columnToCompareWith
     * @return array Numeric array containing the names of the properties that are different,
     *               compared to the provided column
     */
    public function getDiffProperties(Garp_Spawn_Db_Column $columnToCompareWith) {
        $diffPropertyNames = array();

        $refl = new ReflectionObject($this);
        $reflProps = $refl->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($reflProps as $reflProp) {
            $thisProp = $this->{$reflProp->name};
            $thatProp = $columnToCompareWith->{$reflProp->name};
            $isDifferent = ($reflProp->name === 'default' && $thisProp !== $thatProp)
                || $thisProp != $thatProp;

            if (!$this->_isIgnorableDiffProperty($reflProp->name) && $isDifferent) {
                $diffPropertyNames[] = $reflProp->name;
            }
        }

        return $diffPropertyNames;
    }

    protected function _isIgnorableDiffProperty($propName) {
        return in_array($propName, $this->_ignorableDiffProperties);
    }

    /**
     * @return string Renders a MySQL definition to use in a CREATE or ALTER statement,
     *                f.i.: col1 BIGINT UNSIGNED DEFAULT 1
     */
    abstract public function renderSqlDefinition();

    abstract static public function renderFieldSql(Garp_Spawn_Field $field);

    abstract static public function getFieldType(Garp_Spawn_Field $field);

    abstract static public function getRequiredAndDefault(Garp_Spawn_Field $field);

    abstract static public function quoteIfNecessary($fieldType, $value);

    /**
     * @todo: Deze onderstaande functies samenvoegen,
     *        en sowieso alle statische functies eruit schrijven.
     */

    /**
     * @param string $default
     * @param Garp_Spawn_Field $field
     * @return mixed
     */
    static public function convertDefaultIfNecessary($default, Garp_Spawn_Field $field) {
        if (!(is_bool($default) && !$default)) {
            return $default;
        }

        if ($field->type !== 'checkbox') {
            throw new Exception(
                "Field {$field->name} (type: {$field->type}) cannot have " .
                "false (boolean) as default."
            );
        }

        return 0;
    }

    abstract static public function isNumeric($sqlType);

    abstract protected function _convertDefaultIfNecessary($default);

    abstract protected function _parseColumnStatement($line);

    /**
     * @param array   $matches Result of preg_match on sql statement
     * @param string  $prop    Matched property in sql statement,
     *                         i.e. nullable | unsigned | auto_increment
     * @return bool
     */
    protected function _propExistsAndMatches(array $matches, $prop) {
        $existsAndMatches = array_key_exists($prop, $matches) &&
            strcasecmp($matches[$prop], $this->_columnOptions[$prop]) === 0;
        return $existsAndMatches;
    }

}
