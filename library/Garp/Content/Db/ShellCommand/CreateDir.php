<?php
/**
 * Garp_Content_Db_ShellCommand_CreateDir
 * @author David Spreekmeester | Grrr.nl
 */
 class Garp_Content_Db_ShellCommand_CreateDir implements Garp_Content_Db_ShellCommand_Protocol {
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