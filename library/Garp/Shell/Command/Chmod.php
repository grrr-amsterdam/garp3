<?php
/**
 * Garp_Shell_Command_Chmod
 * @author David Spreekmeester | Grrr.nl
 */
 class Garp_Shell_Command_Chmod extends Garp_Shell_Command_Abstract {
 	const COMMAND = "chmod -R %s %s";
	
	/**
	 * @var String $_permission
	 */
	protected $_permission; 

	/**
	 * @var String $_path
	 */
	protected $_path;

	 
	/**
	 * @param 	String	$permission
	 * @param	String	$path
	 */
	public function __construct($permission, $path) {
		 $this->setPermission($permission);
		 $this->setPath($path);
	}	
	
	/**
	 * @return String
	 */
	public function getPermission() {
		return $this->_permission;
	}
	
	/**
	 * @param String $permission
	 */
	public function setPermission($permission) {
		$this->_permission = $permission;
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
 		$permission = $this->getPermission();
		$path		= $this->getPath();
	 	return sprintf(self::COMMAND, $permission, $path);
	}
 }