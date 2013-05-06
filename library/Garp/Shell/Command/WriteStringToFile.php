<?php
/**
 * Garp_Shell_Command_WriteStringToFile
 * @author David Spreekmeester | Grrr.nl
 */
class Garp_Shell_Command_WriteStringToFile extends Garp_Shell_Command_Abstract {
 	const COMMAND = 'echo "%s" > %s';

	/**
	 * @var String $_string
	 */
	protected $_string; 

	/**
	 * @var String $_path
	 */
	protected $_path;

 
	/**
	 * @param 	String	$string
	 * @param	String	$path
	 */
	public function __construct($string, $path) {
		 $this->setString($string);
		 $this->setPath($path);
	}	

	/**
	 * @return String
	 */
	public function getString() {
		return $this->_string;
	}

	/**
	 * @param String $string
	 */
	public function setString($string) {
		$this->_string = $string;
	}
	
	/**
	 * @return String
	 */
	public function getPath() {
		return $this->_path;
	}

	/**
	 * @param String $path
	 */
	public function setPath($path) {
		$this->_path = $path;
	}

	public function render() {
 		$string 	= addslashes($this->getString());
		$path		= $this->getPath();
	 	return sprintf(self::COMMAND, $string, $path);
	}
 }
