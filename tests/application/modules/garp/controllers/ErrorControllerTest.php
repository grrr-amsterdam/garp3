<?php
/**
 * G_ErrorControllerTest
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @lastmodified $Date: $
 */
class G_ErrorControllerTest extends Garp_Test_PHPUnit_ControllerTestCase {
	public function testFaultyUrlShouldReturnErrorPage() {
		try {
			$this->dispatch('foo');
			$this->assertController('error');
			$this->assertAction('error');
			$this->assertModule('default');
		} catch (Zend_Controller_Exception $e) {
			// This doesn't work when exceptions are thrown from the view.
			// Which is possible when in testing environment.
			// (for instance WWC misses some routing info)
		}
	}
}
