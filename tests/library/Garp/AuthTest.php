<?php
/**
 * Garp_AuthTest
 * Tests Garp_Auth
 *
 * @author       $Author: harmen $
 * @modifiedby   $LastChangedBy: harmen $
 * @version      $LastChangedRevision: 6148 $
 * @package      Garp
 * @subpackage   Test
 * @lastmodified $LastChangedDate: 2012-09-03 10:52:37 +0200 (Mon, 03 Sep 2012) $
 * @group        Auth
 */
class Garp_AuthTest extends Garp_Test_PHPUnit_TestCase {
	/**
 	 * @var Garp_Auth
 	 */
	protected $_auth;

	public function setUp() {
		parent::setUp();
		$this->_auth = Garp_Auth::getInstance();
		$this->_auth->setStore(new Garp_Store_Array('Garp_Auth'));
		// empty store by default
		$this->_auth->destroy();
	}

	public function testIsNotLoggedIn() {
		$this->assertEquals(false, $this->_auth->isLoggedIn());
	}

	public function testIsLoggedIn() {
		$this->_auth->store(array('id' => 1, 'email' => 'harmen@grrr.nl'));
		$this->assertEquals(true, $this->_auth->isLoggedIn());
	}
}
