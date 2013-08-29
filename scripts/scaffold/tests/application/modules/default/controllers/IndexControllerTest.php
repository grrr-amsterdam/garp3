<?php
/**
 * Application specific testing methods go here. Any method that starts with 'test' will be executed.
 * Use assertYaddaYadda() methods from Zend_Test_PHPUnit_ControllerTestCase or from G_ControllerTestCase.
 */
class IndexControllerTest extends Garp_Test_PHPUnit_ControllerTestCase {
	public function testIndexAction() {
		$this->assertRouteIsAlive('index', 'index');
    }
}
