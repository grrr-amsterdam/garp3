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

	public function testIsNotLoggedIn() {
		$this->assertEquals(false, Garp_Auth::getInstance()->isLoggedIn());
	}

	public function testIsLoggedIn() {
		$this->_helper->login(array('id' => 1, 'email' => 'harmen@grrr.nl'));
		$this->assertEquals(true, Garp_Auth::getInstance()->isLoggedIn());
	}

}
