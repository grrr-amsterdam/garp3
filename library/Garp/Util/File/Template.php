<?php
/**
 * Garp_Util_File_Template
 * Mini template-engine.
 * Used to generate files. Feed it a template, set some 
 * variables and save the output.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage File
 * @lastmodified $Date: $
 */
class Garp_Util_File_Template {
	/**
	 * The template contents.
	 * @var String
	 */
	protected $_content;
	
	
	/**
	 * Class constructor
	 * @param String $path Path to the template file
	 * @return Void
	 */
	public function __construct($path) {
		$this->_content = file_get_contents($path);
	}
	
	
	/**
	 * Set variable.
	 * @param String $key The variable key (template must contain an "#$key")
	 * @param Mixed $value The variable value
	 * @return Garp_Util_File_Template $this
	 */
	public function setVariable($key, $value) {
		$this->_content = str_replace('#'.$key.'#', (string)$value, $this->_content);
		return $this;
	}
	
	
	/**
	 * Get output.
	 * @return String
	 */
	public function getOutput() {
		return $this->_content;
	}
	
	
	/**
	 * Save output.
	 * @return String
	 */
	public function saveOutput($path) {
		return file_put_contents($path, $this->_content);
	}
}