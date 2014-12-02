<?php
/**
 * Garp_Shell_Command_Chgrp
 * @author David Spreekmeester | Grrr.nl
 */
class Garp_Shell_Command_Chgrp extends Garp_Shell_Command_Abstract {
 	const COMMAND = "chgrp -R %s %s";
	
	/**
	 * @var String $_group
	 */
	protected $_group;

	/**
	 * @var String $_path
	 */
	protected $_path;

	 
	/**
	 * @param 	String	$group
	 * @param	String	$path
	 */
	public function __construct($group, $path) {
		 $this->setGroup($group);
		 $this->setPath($path);
	}	
	
	/**
	 * @return String
	 */
	public function getGroup() {
		return $this->_group;
	}
	
	/**
	 * @param String $group
	 */
	public function setGroup($group) {
		$this->_group = $group;
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
 		$group 	= $this->getGroup();
		$path	= $this->getPath();
	 	return sprintf(self::COMMAND, $group, $path);
	}
 }