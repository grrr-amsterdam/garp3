<?php
/**
 * @package Tests
 * @author  Harmen Janssen <harmen@grrr.nl>
 * @group   Auth
 */
class Garp_Auth_Adapter_PasswordlessTest extends Garp_Test_PHPUnit_TestCase {

    const TEST_EMAIL = 'thedude@garp.com';

    /**
    * Wether to execute these tests, it only makes sense for projects where
    * passwordless authentication is actually enabled.
    */
    protected $_testsEnabled = false;

    protected $_mockData = array(
        'User' => array(),
        'AuthPasswordless' => array(),
    );

    public function testShouldFailWithoutEmail() {
        if (!$this->_testsEnabled) {
            return;
        }
        $pwless = new Garp_Auth_Adapter_Passwordless();
        $pwless->requestToken(array());
        $this->assertTrue(count($pwless->getErrors()) > 0);
    }

    public function testShouldCreateUserRecord() {
        if (!$this->_testsEnabled) {
            return;
        }
        $pwless = new Garp_Auth_Adapter_Passwordless();
        $pwless->requestToken(array('email' => self::TEST_EMAIL));

        $userModel = new Model_User();
        $theUser = $userModel->fetchRow();
        $this->assertFalse(is_null($theUser));
        $this->assertEquals(self::TEST_EMAIL, $theUser->email);
    }

    public function testShouldNotInsertDuplicateRecord() {
        if (!$this->_testsEnabled) {
            return;
        }
        $userModel = new Model_User();
        $userId = $userModel->insert(array('email' => self::TEST_EMAIL));

        $pwless = new Garp_Auth_Adapter_Passwordless();
        $pwless->requestToken(array('email' => self::TEST_EMAIL));

        $users = $userModel->fetchAll();
        $this->assertEquals(1, count($users));
        $this->assertEquals($userId, $users[0]->id);
    }

    public function testShouldCreateAuthRecord() {
        if (!$this->_testsEnabled) {
            return;
        }
        $pwless = new Garp_Auth_Adapter_Passwordless();
        $pwless->requestToken(array('email' => self::TEST_EMAIL));

        $userModel = new Model_User();
        $theUser = $userModel->fetchRow();

        $authModel = new Model_AuthPasswordless();
        $authRecord = $authModel->fetchRow();

        $this->assertFalse(is_null($authRecord));
        $this->assertEquals($theUser->id, $authRecord->user_id);
        $this->assertTrue(is_string($authRecord->token));
        $this->assertTrue(strlen($authRecord->token) > 0);
        $this->assertTrue(is_string($authRecord->token_expiration_date));
        $this->assertTrue(strlen($authRecord->token_expiration_date) > 0);
    }

    public function testShouldSendEmail() {
        if (!$this->_testsEnabled) {
            return;
        }
        $pwless = new Garp_Auth_Adapter_Passwordless();
        $pwless->requestToken(array('email' => self::TEST_EMAIL));

        $userModel = new Model_User();
        $theUser = $userModel->fetchRow();

        $authModel = new Model_AuthPasswordless();
        $authRecord = $authModel->fetchRow();

        $tokenUrl = new Garp_Util_FullUrl(array(array('method' => 'passwordless'), 'auth_submit')) .
            '?uid=' . $theUser->id . '&token=' . $authRecord->token;

        $storedMessage = file_get_contents(
            GARP_APPLICATION_PATH .
            '/../tests/tmp/' . self::TEST_EMAIL . '.tmp'
        );

        $expectedMessage = Garp_Util_String::interpolate(
            $this->_getMockEmailMessage(), array(
            'LOGIN_URL' => $tokenUrl
            )
        );

        // Pass thru actual Mime part, otherwise the two wil never be the same
        $mp = new Zend_Mime_Part($expectedMessage);
        $mp->encoding = Zend_Mime::ENCODING_QUOTEDPRINTABLE;
        $mp->type = Zend_Mime::TYPE_TEXT;
        $mp->disposition = Zend_Mime::DISPOSITION_INLINE;
        $mp->charset = 'iso-8859-1';

        // Just check for the token url. Message is encoded so checking for entire message to be
        // correct is overly complex (and not the responsibility of this unit test).
        $this->assertTrue(strpos($storedMessage, $mp->getContent("\r\n")) !== false);
    }

    public function testShouldFailOnFalsyParams() {
        if (!$this->_testsEnabled) {
            return;
        }
        $pwless = new Garp_Auth_Adapter_Passwordless();
        $response = $pwless->acceptToken(null, null);
        $this->assertFalse($response);
        $this->assertEquals($pwless->getErrors(), array(__('Insufficient data received')));
    }

    public function testShouldFailOnInvalidToken() {
        if (!$this->_testsEnabled) {
            return;
        }
        $pwless = new Garp_Auth_Adapter_Passwordless();
        $response = $pwless->acceptToken('19398829849', 1);
        $this->assertFalse($response);
        $this->assertEquals($pwless->getErrors(), array(__('passwordless token not found')));
    }

    public function testShouldFailOnStrangersToken() {
        if (!$this->_testsEnabled) {
            return;
        }
        $userModel = new Model_User();
        $userModel->insert(array('email' => 'henk@grrr.nl', 'id' => 1));
        $userModel->insert(array('email' => 'jaap@grrr.nl', 'id' => 2));
        $authModel = new Model_AuthPasswordless();
        $authModel->insert(
            array(
                'token' => '12345',
                'token_expiration_date' => date('Y-m-d H:i:s', strtotime('+30 minutes')),
                'user_id' => 2
            )
        );

        $pwless = new Garp_Auth_Adapter_Passwordless();
        $response = $pwless->acceptToken('12345', 1);
        $this->assertFalse($response);
        $this->assertEquals($pwless->getErrors(), array(__('passwordless token not found')));
    }

    public function testShouldFailOnExpiredToken() {
        if (!$this->_testsEnabled) {
            return;
        }
        instance(new Model_User())->insert(
            array(
            'id' => 5,
            'email' => 'henk@grrr.nl'
            )
        );
        instance(new Model_AuthPasswordless())->insert(
            array(
            'user_id' => 5,
            'token' => 'abc',
            'token_expiration_date' => date(
                'Y-m-d H:i:s',
                strtotime('-1 hour')
            )
            )
        );

        $pwless = new Garp_Auth_Adapter_Passwordless();
        $response = $pwless->acceptToken('abc', 5);
        $this->assertFalse($response);
        $this->assertEquals(
            array(__('passwordless token expired')),
            $pwless->getErrors()
        );
    }

    public function testShouldAcceptValidToken() {
        if (!$this->_testsEnabled) {
            return;
        }
        $pwless = new Garp_Auth_Adapter_Passwordless();
        $response = $pwless->requestToken(array('email' => self::TEST_EMAIL));

        $userId = instance(new Model_User)->fetchRow()->id;
        $token  = instance(new Model_AuthPasswordless)->fetchRow()->token;

        $response = $pwless->acceptToken($token, $userId);
        $this->assertTrue($response instanceof Garp_Db_Table_Row);
    }

    public function testShouldRejectClaimedToken() {
        if (!$this->_testsEnabled) {
            return;
        }
        $pwless = new Garp_Auth_Adapter_Passwordless();
        $response = $pwless->requestToken(array('email' => self::TEST_EMAIL));

        // manually claim token
        instance(new Model_AuthPasswordless)->update(array('claimed' => 1), 'id > 0');

        $userId = instance(new Model_User)->fetchRow()->id;
        $token  = instance(new Model_AuthPasswordless)->fetchRow()->token;

        $response = $pwless->acceptToken($token, $userId);
        $this->assertFalse($response);
        $this->assertEquals(array(__('passwordless token claimed')), $pwless->getErrors());
    }

    protected function _getMockEmailMessage() {
        return "Hi, You can login with the following URL: %LOGIN_URL%. " .
            "Have fun on the website! Kind regards, the team";
    }

    public function generateEmailFilename($transport) {
        return $transport->recipients . '.tmp';
    }

    public function setUp() {
        // Only execute tests when passwordless is actually one of the configured adapters for
        // this project.
        $this->_testsEnabled = isset(Zend_Registry::get('config')->auth->adapters->passwordless);
        if (!$this->_testsEnabled) {
            return;
        }
        parent::setUp();

        $this->_helper->injectConfigValues(
            array(
            'app' => array(
                'domain' => 'testing.example.com'
            ),
            'organization' => array(
                'email' => self::TEST_EMAIL
            ),
            'auth' => array(
                'adapters' => array(
                    'passwordless' => array(
                        'email_body' => $this->_getMockEmailMessage(),
                        'email_subject' => "Here's your login token",
                        'email_body_snippet_identifier' => null,
                        'email_subject_snippet_identifier' => null,
                        'email_transport_method' => 'Zend_Mail_Transport_File',
                        'email_transport_options' => array(
                            'path' => GARP_APPLICATION_PATH . '/../tests/tmp',
                            'callback' => array($this, 'generateEmailFilename')
                        )
                    )
                )
            )
            )
        );

    }

    public function tearDown() {
        if (!$this->_testsEnabled) {
            return;
        }
        parent::tearDown();

        if (file_exists(GARP_APPLICATION_PATH . '/../tests/tmp/' . self::TEST_EMAIL . '.tmp')) {
            unlink(GARP_APPLICATION_PATH . '/../tests/tmp/' . self::TEST_EMAIL . '.tmp');
        }

    }
}
