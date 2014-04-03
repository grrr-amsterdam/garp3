<?php
/**
 * Garp_Cli_Template
 * Use template files to create actual files from
 * cli commands.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class Garp_Cli_Template {
	/**
	 * The template filename
	 * @var String
	 */
	protected $_filename = '';
	
	
	/**
	 * Properties to be used in the template
	 * @var Array
	 */
	protected $_props = array();
	
	
	/**
	 * Class constructor
	 * @param String $filename The template file
	 * @return Void
	 */
	public function __construct($filename) {
		$this->_filename = $filename;
	}
	
	
	/**
	 * Render the template file to a string with the properties in place.
	 * @return String
	 */
	public function render() {
		$path = APPLICATION_PATH.'/../garp/scripts/templates/build/'.$this->_filename;
		$template = file_get_contents($path);
		$keys = array_map(function($prop) {
			return '${'.$prop.'}';
		}, array_keys($this->_props));
		$values = array_values($this->_props);
		$template = str_replace($keys, $values, $template);
		return $template;
	}
	
	
	/**
	 * Set property
	 * @param String $key
	 * @param String $value
	 * @return $this
	 */
	public function setProperty($key, $value) {
		if (is_array($value)) {
			$value = $this->formatArray($value);
		}
		$this->_props[$key] = $value;
		return $this;
	}
	
	
	/**
	 * Get property
	 * @param String $key
	 * @return String
	 */
	public function getProperty($key) {
		return array_key_exists($key, $this->_props) ? $this->_props[$key] : null;
	}
	
	
	/**
	 * Magic setter
	 * @param String $key
	 * @param String $value
	 * @return Void
	 */
	public function __set($key, $value) {
		$this->setProperty($key, $value);
	}
	
	
	/**
	 * Magic getter
	 * @param String $key
	 * @return String
	 */
	public function __get($key) {
		return $this->getProperty($key);
	}
	
	
	/**
	 * Format an array to string
	 * @param Array $a
	 * @return String 
	 */
	public function formatArray(array $a) {
		$out = "array(";
		foreach ($a as $key => $val) {
			$key = (string)$key;
			if (is_array($val)) {
				$val = $this->formatArray($val);
			} else {
				$val = (string)$val;
			}
			$out .= "\t$key => $val,\n";
		}
		$out .= ")";
		return $out;
	}
}