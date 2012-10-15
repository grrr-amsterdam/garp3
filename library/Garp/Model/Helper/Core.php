<?php
/**
 * Garp_Model_Helper_Core
 * Helper objects that extend from this abstract base class are 
 * notified either first or last, and are part of 
 * Garp's core functionality.
 * This means you don't have to register them manually as observers.
 * 
 * An example is Cachable; this is standard core functionality
 * of Garp and it's not the developer's responsibility to register
 * it — it will be registered automatically.
 * 
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Model
 * @lastmodified $Date: $
 */
abstract class Garp_Model_Helper_Core extends Garp_Model_Helper {
	/**
	 * Storing state; these core helpers may be executed before
	 * all other observers or after all observers.
	 * @var String
	 */
	const EXECUTE_FIRST = 'first';
	const EXECUTE_LAST  = 'last';
	
	
	/**
	 * Wether to execute before or after other observers.
	 * @var String
	 */
	protected $_executionPosition = self::EXECUTE_LAST;
	
	
	/**
	 * Get execution position
	 * @return String
	 */
	public function getExecutionPosition() {
		return $this->_executionPosition;
	}	
}