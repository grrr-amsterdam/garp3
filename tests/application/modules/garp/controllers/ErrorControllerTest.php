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
		$this->dispatch('foo');
		$this->assertController('error');
		$this->assertAction('error');
		$this->assertModule('default');
	}
}