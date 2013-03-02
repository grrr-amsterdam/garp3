<?php
/**
 * Garp_Content_Db_Mediator
 * 
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
class Garp_Content_Db_Mediator {	
	
	/**
	 * @var Garp_Content_Db_Server_*
	 */
	protected $_source;

	/**
	 * @var Garp_Content_Db_Server_*
	 */
	protected $_target;

	
	/**
	 * @param String $sourceEnv
	 * @param String $targetEnv
	 */
	public function __construct($sourceEnv, $targetEnv) {
		$this->setSource($sourceEnv);
		$this->setTarget($targetEnv);
	}
	

	/**
	 * Transfers database structure and data from source to target.
	 */
	public function transfer() {
		$target = $this->getTarget();
		$target->backup();
	}
	
	
	/**
	 * @param String $environment
	 */
	public function setSource($environment) {
		$this->_source = Garp_Content_Db_Server_Factory::create($environment);
	}

	/**
	 * @param String $environment
	 */
	public function setTarget($environment) {
		$this->_target = Garp_Content_Db_Server_Factory::create($environment);
	}

	public function getSource() {
		return $this->_source;
	}

	public function getTarget() {
		return $this->_target;
	}

}