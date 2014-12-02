<?php
/**
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Model
 */
abstract class Garp_Spawn_MySql_Key {
	public function __construct($line) {
		$params = $this->_parse($line);
		foreach ($params as $key => $value) {
			if (is_numeric($key))
				unset($params[$key]);
		}

		foreach ($params as $paramName => $paramValue) {
			if (property_exists($this, $paramName)) {
				$this->{$paramName} = $paramValue;
			} else {
				throw new Exception("'{$param}' is not a valid property for this type of index. Try: ".implode(", ", array_keys(get_object_vars($this))));
			}
		}
	}
}