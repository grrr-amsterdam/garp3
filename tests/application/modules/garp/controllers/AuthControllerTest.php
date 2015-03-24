<?php
/**
 * @group Controllers
 *
 * @todo Lots of assumptions: the presence of a user model, local db auth storage.
 * Garp routes must be used (or at least a route called 'auth_submit').
 *
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
		$this->_helper->injectConfigValues(array(
			'resources' => array(
				'router' => array(
					'locale' => array(
						'enabled' => false
					)
				)
			)
		));

		$this->_auth = Garp_Auth::getInstance();
		$this->_auth->setStore(new Garp_Store_Array('Garp_Auth'));
		$this->_auth->destroy();

		$userModel = new Model_User();
		// sanity check
		$sampleRow = $userModel->createRow();
		if (isset($sampleRow->name)) {
			$this->_mockUser['name'] = $this->_mockUser['first_name'];
			unset($this->_mockUser['first_name']);
		}

		$userModel->delete('id > 0');
		$userModel->insert($this->_mockUser);
	}

	public function tearDown() {
		$this->_auth->destroy();
		$userModel = new Model_User();
		$userModel->delete('id > 0');
	}

}
