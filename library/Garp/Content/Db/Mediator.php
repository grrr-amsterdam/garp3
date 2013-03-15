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
     * Singleton instance
     * @var Garp_Auth
     */
    private static $_instance = null;

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
		$this->setSource($sourceEnv, $targetEnv);
		$this->setTarget($targetEnv, $sourceEnv);
	}

	/**
	 * @param String $environment
	 */
	public function setSource($environment, $otherEnvironment) {
		$this->_source = Garp_Content_Db_Server_Factory::create($environment, $otherEnvironment);
	}

	/**
	 * @param String $environment
	 */
	public function setTarget($environment, $otherEnvironment) {
		$this->_target = Garp_Content_Db_Server_Factory::create($environment, $otherEnvironment);
	}

	public function getSource() {
		return $this->_source;
	}

	public function getTarget() {
		return $this->_target;
	}	
	

}