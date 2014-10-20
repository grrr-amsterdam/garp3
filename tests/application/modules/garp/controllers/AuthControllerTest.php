<?php
/**
 * @group Controllers
 */
class G_AuthControllerTest extends Garp_Test_PHPUnit_ControllerTestCase {
	protected $_mockUser = array(
		'first_name' => 'Harmen',
		'email' => 'harmen@grrr.nl',
		'password' => 'mymilkshakebringsalltheboystotheyard',
		'role' => 'user'
	);

	protected $_auth;

	public function testProcessShouldLoginUser() {
		// @todo This won't work for projects where garp_routes are not used.
		// How to detect?
		$url = Zend_Controller_Front::getInstance()->getRouter()->assemble(array(
		), 'auth_submit');

		$this->request->setMethod('POST')
			->setPost(array(
				'email' => $this->_mockUser['email'],
				'password' => $this->_mockUser['password']
			));
		$this->dispatch($url);

		$this->assertEquals(true, $this->_auth->isLoggedIn());
	}

	public function setUp() {
		parent::setUp();
		$this->_auth = Garp_Auth::getInstance();
		$this->_auth->setStore(new Garp_Store_Array('Garp_Auth'));
		$this->_auth->destroy();

		$userModel = new Model_User();
		$userModel->delete('id > 0');
		$userModel->insert($this->_mockUser);
	}

	public function tearDown() {
		$this->_auth->destroy();
		$userModel = new Model_User();
		$userModel->delete('id > 0');
	}

}
