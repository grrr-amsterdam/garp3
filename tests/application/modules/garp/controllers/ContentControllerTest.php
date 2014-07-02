<?php
/**
 * @group Controllers
 */
class G_ContentControllerTest extends Garp_Test_PHPUnit_ControllerTestCase {

	public function setUp() {
		$this->request->clearCookies();
	}

	public function testVisitorCannotAccessCms() {
		$this->dispatch('/admin');
		$this->assertRedirectTo('/g/auth/login');
	}

	public function testAdminCanAccessCms() {
		$this->_mockLogin();
		$this->dispatch('/admin');
		$this->assertController('content');
		$this->request->setMethod('GET');
		$this->assertAction('admin');
	}

	protected function _mockLogin() {
		Garp_Auth::getInstance()->store(array(
			'id'    => 1,
			'name'  => 'Harmen',
			'email' => 'harmen@grrr.nl',
			'role'  => 'admin',
		));
		$this->request->setCookie('Garp_Auth', json_encode(
			Garp_Auth::getInstance()->getStore()->toArray()
		));
	}		
}
