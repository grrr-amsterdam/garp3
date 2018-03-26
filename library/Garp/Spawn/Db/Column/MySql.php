<?php
/**
 * @package Garp_Spawn_Db_Column
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Spawn_Db_Column_MySql extends Garp_Spawn_Db_Column {

    public function renderSqlDefinition() {
        $nodes = array();
        $nodes[] = '`' . $this->name . '`';

        $typeStatement = $this->type;
        if ($this->length || $this->decimals) {
            $typeStatement.= '('
                . ($this->length ?: $this->decimals)
                . ($this->decimals ? (',' . $this->decimals) : '')
                . ')';
        } elseif ($this->type === 'enum' || $this->type === 'set') {
            $typeStatement.= '(' . $this->options . ')';
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
        $type = Garp_Spawn_Db_Column::getFieldType($field);
        $reqAndDef = Garp_Spawn_Db_Column::getRequiredAndDefault($field);
        if ($reqAndDef) {
            $reqAndDef = ' ' . $reqAndDef;
        }
        $autoIncr = $field->name === 'id' ? ' AUTO_INCREMENT' : '';
        $out = "  `{$field->name}` {$type}{$reqAndDef}{$autoIncr}";
        return $out;
    }


    static public function getFieldType(Garp_Spawn_Field $field) {
        switch ($field->type) {
        case 'numeric':
            if ($field->float) {
                $type = 'double';
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
        case 'set':
            $options = $field->options;
            if (is_object($options) || (is_array($options) && key($options) !== 0)) {
                //  this enum field has labels attached to it,
                //  but only the values are stored in the database.
                $options = array_keys((array)$options);
            }
            return "{$field->type}('" . implode($options, "','") . "')";

        default:
            throw new Exception(
                "The '{$field->type}' field type can't be translated to " .
                "a MySQL field type as of yet."
            );
        }
    }

    static public function getRequiredAndDefault(Garp_Spawn_Field $field) {
        $out = array();
        if ($field->type === 'checkbox'
            || ($field->required && $field->relationType !== 'hasOne')
        ) {
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
        $isStringLike = !is_numeric($value) && !is_bool($value) && !is_null($value);

        if ($fieldType === 'enum' || $isStringLike) {
            $decorator = "'";
        }
        return $decorator . $value . $decorator;
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

    protected function _convertDefaultIfNecessary($default) {
        if (!(is_bool($default) && !$default)) {
            return $default;
        }

        if ($this->type !== 'tinyint(1)') {
            throw new Exception("A {$this->type} column cannot have false (boolean) as default.");
        }

        return 0;
    }

    protected function _parseColumnStatement($line) {
        $matches = [];
        $pattern = '/`(?P<name>\w+)` (?P<type>[\w]*)(\(((?P<length>\d*),?\s*(?P<decimals>\d*))(?P<options>[^\)]*)\))? ?(?P<unsigned>UNSIGNED)? ?(?P<not_nullable>NOT NULL)? ?(?P<auto_increment>AUTO_INCREMENT)? ?(DEFAULT \'?(?P<default>[^\',]*)\'?)?/i';
        preg_match($pattern, trim($line), $matches);
        $matches['_statement'] = $matches[0];

        foreach ($matches as $key => $match) {
            if (is_numeric($key)) {
                unset($matches[$key]);
            }
        }

        if (array_key_exists('default', $matches)) {
            if ($matches['default'] === 'NULL') {
                $matches['default'] = null;
            } elseif ($this->isNumeric($matches['type'])) {
                if (strpos($matches['default'], '.') !== false) {
                    $matches['default'] = (float)$matches['default'];
                } else {
                    $matches['default'] = (int)$matches['default'];
                }
            }
        }

        $matches['nullable'] = !$this->_propExistsAndMatches($matches, 'not_nullable');
        unset($matches['not_nullable']);

        $matches['unsigned'] = $this->_propExistsAndMatches($matches, 'unsigned');
        $matches['auto_increment'] = $this->_propExistsAndMatches($matches, 'auto_increment');

        return $matches;
    }

}
