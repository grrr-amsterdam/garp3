<?php
/**
 * Garp_Shell_Command_Chown
 * @author David Spreekmeester | Grrr.nl
 */
 class Garp_Shell_Command_Chown extends Garp_Shell_Command_Abstract {
 	const COMMAND = "chown -R %s %s";
	
	/**
	 * @var String $_user
	 */
	protected $_user; 

	/**
	 * @var String $_path
	 */
	protected $_path;

	 
	/**
	 * @param 	String	$user
	 * @param	String	$path
	 */
	public function __construct($user, $path) {
		 $this->setUser($user);
		 $this->setPath($path);
	}	
	
	/**
	 * @return String
	 */
	public function getUser() {
		return $this->_user;
	}
	
	/**
	 * @param String $user
	 */
	public function setUser($user) {
		$this->_user = $user;
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
 		$user 	= $this->getUser();
		$path	= $this->getPath();
	 	return sprintf(self::COMMAND, $user, $path);
	}
 }