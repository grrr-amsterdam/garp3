<?php
/**
 * Garp_Test_PHPUnit_TestCase
 * Adds some convenience methods to Tests.
 *
 * @author       $Author: harmen $
 * @modifiedby   $LastChangedBy: harmen $
 * @version      $LastChangedRevision: 6134 $
 * @package      Garp
 * @subpackage   Test
 * @lastmodified $LastChangedDate: 2012-08-29 23:32:18 +0200 (Wed, 29 Aug 2012) $
 */
abstract class Garp_Test_PHPUnit_TestCase extends PHPUnit_Framework_TestCase {
	/** @var Zend_Db_Adapter_Abstract */
	protected $_db;

	/** @var Garp_Test_PHPUnit_Helper */
	protected $_helper;

	public function __construct() {
		$this->_helper = new Garp_Test_PHPUnit_Helper();
		parent::__construct();
	}

	/**
 	 * Get database adapter for executing queries quickly.
 	 * It will be configured as defined in application.ini.
 	 * @return Zend_Db_Adapter_Abstract
 	 */
	public function getDatabaseAdapter() {
		if (!$this->_db) {
			$ini = Zend_Registry::get('config');
			$this->_db = Zend_Db::factory($ini->resources->db);
		}
		return $this->_db;
	}

}
