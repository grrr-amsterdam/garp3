<?php
/**
 * @group Controllers
 */
class G_ContentControllerTest extends Garp_Test_PHPUnit_ControllerTestCase {

	public function setUp() {
		Garp_Auth::getInstance()->destroy();
	}

	public function testVisitorCannotAccessCms() {
		$this->dispatch('/admin');
		// @todo More specific: what should it redirect to?
		// But we can't figure out which route was redirected to...
		// Bugger.
		$this->assertRedirect('Could happen due to acl being disabled in auth.ini');
	}

	public function testAdminCanAccessCms() {
		$this->_mockLogin();
		$this->dispatch('/admin');
		$this->assertController('content');
		$this->assertAction('admin');
	}

	public function testUserCanBeBlockedBasedOnIpFilter() {
		$this->_mockLogin();

		$this->_helper->injectConfigValues(array(
			'cms' => array(
				'ipfilter' => array(
					'11.122.21.21'
				)
			)
		));
		// mock ip address
		$_SERVER['REMOTE_ADDR'] = '99.100.192.12';

		$this->dispatch('/admin');
		$this->assertRedirect();
	}

	public function testUserWithMatchingIpIsAllowed() {
		$this->_mockLogin();

		$this->_helper->injectConfigValues(array(
			'cms' => array(
				'ipfilter' => array(
					'11.122.21.21'
				)
			)
		));
		// mock ip address
		$_SERVER['REMOTE_ADDR'] = '11.122.21.21';

		$this->dispatch('/admin');
		$this->assertController('content');
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
