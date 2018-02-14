<?php
/**
 * @package Garp_Spawn_Db
 * @author  David Spreekmeester <david@grrr.nl>
 */
abstract class Garp_Spawn_Db_Key {

    public function __construct($line) {
        $params = $this->_parse($line);
        foreach ($params as $key => $value) {
            if (is_numeric($key))
                unset($params[$key]);
        }

        foreach ($params as $paramName => $paramValue) {
            if (!property_exists($this, $paramName)) {
                $options = implode(", ", array_keys(get_object_vars($this)));
                throw new Exception(
                    "'{$param}' is not a valid property for this type of index. Try: {$options}"
                );
            }
            $this->{$paramName} = $paramValue;
        }
    }

}
