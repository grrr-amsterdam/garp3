<?php
/**
 * Garp_Browsebox_Filter_Abstract
 * Blueprint for Browsebox filters.
 * A filter can be used to add small dynamic conditions determined at runtime.
 * They may either modify the SELECT object (Zend_Db_Select) or fetch the 
 * results for the browsebox.
 * 
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Browsebox
 * @lastmodified $Date: $
 */
abstract class Garp_Browsebox_Filter_Abstract {
	/**
	 * Internal id
	 * @var String
	 */
	protected $_id;

	
	/**
	 * Configuration
	 * @var Array
	 */
	protected $_config;


	/**
	 * Class constructor
	 * @param String $id
	 * @param Array $params
	 * @return Void
	 */
	public function __construct($id, array $config = array()) {
		$this->_id = $id;
		$this->_config = $config;
	}


	/**
	 * Get the id 
	 * @return String
	 */
	public function getId() {
		return $this->_id;
	}


	/**
	 * Setup the filter
	 * @param Array $params
	 * @return Void
	 */
	abstract public function init(array $params = array());


	/**
	 * Modify the Select object
	 * @param Zend_Db_Select $select
	 * @return Void
	 */
	public function modifySelect(Zend_Db_Select &$select) {
		// throw an exception indicating the method is not used
		throw new Garp_Browsebox_Filter_Exception_NotApplicable();
	}


	/**
	 * Fetch results
	 * @param Zend_Db_Select $select The specific select for this instance.
	 * @param Garp_Browsebox $browsebox The browsebox, made available to fetch metadata from.
	 * @return Void
	 */
	public function fetchResults(Zend_Db_Select $select, Garp_Browsebox $browsebox) {
		// throw an exception indicating the method is not used
		throw new Garp_Browsebox_Filter_Exception_NotApplicable();
	}


	/**
	 * Fetch max amount of chunks.
	 * @param Zend_Db_Select $select The specific select for this instance.
	 * @param Garp_Browsebox $browsebox The browsebox, made available to fetch metadata from.
	 * @return Void
	 */
	public function fetchMaxChunks(Zend_Db_Select $select, Garp_Browsebox $browsebox) {
		// throw an exception indicating the method is not used
		throw new Garp_Browsebox_Filter_Exception_NotApplicable();
	}
}
