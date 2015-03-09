<?php
/**
 * @group Models
 */
class G_Model_UserTest extends Garp_Test_PHPUnit_TestCase {

	protected $_userData = array(
		array(
			'first_name' => 'Harmen',
			'last_name' => 'Janssen',
			'email' => 'harmen@grrr.nl',
			'role' => 'developer'
		),
		array(
			'first_name' => 'Frits',
			'last_name_prefix' => 'van der',
			'last_name' => 'Plof',
			'email' => 'frits@grrr.nl'
		),
		array(
			'first_name' => 'Henk',
			'last_name' => 'Billekes',
			'email' => 'henk@grrr.nl'
		),
		array(
			'last_name' => 'de Beuker',
			'email' => 'henk@grrr.nl'
		)
	);

	public function testNewUserShouldGetPrefilledData() {
		$modelUser = new G_Model_User();

		$newUserData = array('email' => 'harmen@grrr.nl');
		$prefilledData = $modelUser->getPrefilledData($newUserData);

		$this->assertArrayHasKey('email', $prefilledData);
		$this->assertArrayHasKey('first_name', $prefilledData);
		$this->assertArrayHasKey('last_name', $prefilledData);
		$this->assertArrayHasKey('role', $prefilledData);

		$this->assertEquals($this->_userData[0]['email'], $prefilledData['email']);
		$this->assertEquals($this->_userData[0]['first_name'], $prefilledData['first_name']);
		$this->assertEquals($this->_userData[0]['last_name'], $prefilledData['last_name']);
		$this->assertEquals($this->_userData[0]['role'], $prefilledData['role']);
	}

	public function testNewUserDataShouldOverwritePredefinedData() {
		$modelUser = new G_Model_User();

		$newUserData = array(
			'email' => 'frits@grrr.nl',
			'first_name' => 'Hank',
			'last_name' => 'O\'Reilly'
		);
		$prefilledData = $modelUser->getPrefilledData($newUserData);

		$this->assertArrayHasKey('email', $prefilledData);
		$this->assertArrayHasKey('first_name', $prefilledData);
		$this->assertArrayHasKey('last_name', $prefilledData);

		$this->assertNotEquals($this->_userData[1]['first_name'], $prefilledData['first_name']);
		$this->assertEquals($newUserData['first_name'], $prefilledData['first_name']);
		$this->assertNotEquals($this->_userData[1]['last_name'], $prefilledData['last_name']);
		$this->assertEquals($newUserData['last_name'], $prefilledData['last_name']);
	}

	public function testUserDataWithoutEmailShouldNotRaiseError() {
		$modelUser = new G_Model_User();
		$newUserData = array(
			'first_name' => 'Hank',
			'last_name' => 'O\'Reilly'
		);
		$prefilledData = $modelUser->getPrefilledData($newUserData);
		$this->assertEquals($newUserData, $prefilledData);
	}

	public function testUserDataWithUnknownEmailShouldNotRaiseError() {
		$modelUser = new G_Model_User();
		$newUserData = array(
			'first_name' => 'Hank',
			'last_name' => 'O\'Reilly',
			'email' => 'hankoreilly@grrr.nl'
		);
		$prefilledData = $modelUser->getPrefilledData($newUserData);
		$this->assertEquals($newUserData, $prefilledData);
	}

	public function testMultipleEntriesShouldBeCombined() {
		// edge case
		$modelUser = new G_Model_User();
		$newUserData = array('email' => 'henk@grrr.nl');

		$prefilledData = $modelUser->getPrefilledData($newUserData);
		$this->assertArrayHasKey('first_name', $prefilledData);
		$this->assertArrayHasKey('last_name', $prefilledData);

		$this->assertEquals($this->_userData[2]['first_name'], $prefilledData['first_name']);
		$this->assertEquals($this->_userData[3]['last_name'], $prefilledData['last_name']);
	}

	public function setUp() {
		parent::setUp();
		$this->_helper->injectConfigValues(array(
			'auth' => array(
				'users' => $this->_userData
			)
		));
	}
}
