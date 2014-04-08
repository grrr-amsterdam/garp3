<?php
/**
 * Garp_Shell_Command_CreateDir
 * @author David Spreekmeester | Grrr.nl
 */
 class Garp_Shell_Command_CreateDir extends Garp_Shell_Command_Abstract {
 	const COMMAND_CREATE_DIR = "mkdir -p -m 770 %s";	 
	 
	protected $_dir;
	 
	 
	public function __construct($dir) {
		 $this->setDir($dir);
	}
	 
 	public function getDir() {
 		return $this->_dir;
 	}

 	public function setDir($dir) {
 		$this->_dir = $dir;
 	}
	 
	public function render() {
 		$dir = $this->getDir();
	 	return sprintf(self::COMMAND_CREATE_DIR, $dir);
	}
 }