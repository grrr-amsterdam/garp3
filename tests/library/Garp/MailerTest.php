<?php
/**
 * @package Tests
 * @author  Harmen Janssen <harmen@grrr.nl>
 * @group   Mailer
 */
class Garp_MailerTest extends Garp_Test_PHPUnit_TestCase {

    public function test_mailer_should_use_sendmail_transport_by_default() {
        // Disable a possibly existing amazon configuration
        $this->_helper->injectConfigValues(
            array(
            'amazon' => null,
            'mailer' => array(
                'sendMail' => true
            )
            )
        );
        $mailer = new Garp_Mailer();
        $this->assertTrue($mailer->getTransport() instanceof Zend_Mail_Transport_Sendmail);
    }

    public function test_mailer_should_use_amazon_transport_if_available() {
        $this->_helper->injectConfigValues(
            array(
            'amazon' => array(
                'ses' => array(
                    'accessKey' => '1234567890',
                    'secretKey' => 'abc',
                )
            ),
            'mailer' => array(
                'sendMail' => true
            )
            )
        );
        $mailer = new Garp_Mailer();
        $this->assertTrue($mailer->getTransport() instanceof Garp_Mail_Transport_AmazonSes);
    }

    public function test_mailer_should_use_file_transport_if_mailing_is_disabled() {
        $this->_helper->injectConfigValues(
            array(
            'mailer' => array(
                'sendMail' => false
            )
            )
        );
        $mailer = new Garp_Mailer();
        $this->assertTrue($mailer->getTransport() instanceof Zend_Mail_Transport_File);

        // disable thru amazon ses config
        $this->_helper->injectConfigValues(
            array(
            'mailer' => array(
                'sendMail' => false
            ),
            'amazon' => array(
                'ses' => array(
                    'accessKey' => '1234567890',
                    'sendMail' => false
                )
            )
            )
        );
        $this->assertTrue($mailer->getTransport() instanceof Zend_Mail_Transport_File);
    }

    /**
     * @expectedException Garp_Util_Configuration_Exception
     */
    public function test_mailer_should_throw_exception_on_missing_param_to() {
        $mailer = new Garp_Mailer();
        $params = $this->_getParams();
        unset($params['to']);
        $mailer->send($params);
    }

    /**
     * @expectedException Garp_Util_Configuration_Exception
     */
    public function test_mailer_should_throw_exception_on_missing_param_subject() {
        $mailer = new Garp_Mailer();
        $params = $this->_getParams();
        unset($params['subject']);
        $mailer->send($params);
    }

    public function test_mailer_should_throw_exception_on_missing_param_message() {
        $this->expectException(Garp_Util_Configuration_Exception::class);

        $mailer = new Garp_Mailer();
        $params = $this->_getParams();
        unset($params['message']);
        $mailer->send($params);
    }

    public function test_mailer_should_throw_exception_when_from_address_unknown() {
        $this->expectException(Garp_Mailer_Exception_CannotResolveFromAddress::class);

        $this->_helper->injectConfigValues(
            array(
            'mailer' => array(
                'fromAddress' => null
            ),
            'amazon' => array(
                'ses' => array(
                    'fromAddress' => null
                )
            )
            )
        );
        $mailer = new Garp_Mailer();
        $mailer->send($this->_getParams());
    }

    public function test_mailer_should_find_from_address() {
        $this->_helper->injectConfigValues(
            array(
            'amazon' => null,
            'mailer' => array(
                'fromAddress' => 'henk@grrr.nl'
            )
            )
        );
        $mailer = new Garp_Mailer();
        $this->assertEquals($mailer->getFromAddress(), 'henk@grrr.nl');

        $this->_helper->injectConfigValues(
            array(
            'amazon' => array(
                'ses' => array(
                    'accessKey' => '1234567890',
                    'fromAddress' => 'jaap@grrr.nl'
                )
            ),
            'mailer' => null
            )
        );
        $mailer = new Garp_Mailer();
        $this->assertEquals($mailer->getFromAddress(), 'jaap@grrr.nl');
    }

    public function test_mailer_should_read_default_attachment() {
        $this->_helper->injectConfigValues(
            array(
            'mailer' => array(
                'attachments' => array(
                    'site-logo' => '/path/to/my/logo.png'
                )
            )
            )
        );
        $mailer = new Garp_Mailer();
        $this->assertEquals(
            $mailer->getDefaultAttachments(), array(
            'site-logo' => '/path/to/my/logo.png'
            )
        );
    }

    public function test_mailer_should_read_default_html_template() {
        $this->_helper->injectConfigValues(
            array(
            'mailer' => array(
                'template' => 'email/email.phtml'
            )
            )
        );
        $mailer = new Garp_Mailer();
        $this->assertEquals('email/email.phtml', $mailer->getDefaultHtmlTemplate());
    }

    public function test_mailer_should_mail() {
        $this->_helper->injectConfigValues(
            array(
            'mailer' => array(
                'template' => null,
                'attachments' => null
            )
            )
        );

        $targetPath = GARP_APPLICATION_PATH . '/../tests/tmp';
        $mailer = new Garp_Mailer();
        $mailer->setTransport(
            new Zend_Mail_Transport_File(
                array(
                'path' => $targetPath,
                'callback' => function () {
                    return 'mail.txt';
                }
                )
            )
        );
        $mailer->send($this->_getParams());

        $this->assertTrue(file_exists($targetPath . '/mail.txt'));

        /**
         * Note: verifying the contents of the file would be testing Zend_Mail_Transport_File.
         * Let's for now assume if the file is there, the content is correct.
         */

        unlink($targetPath . '/mail.txt');
    }

    /**
     * Some default mail params
     */
    protected function _getParams() {
        return array(
            'to' => 'dog@gmail.com',
            'subject' => 'Hello',
            'message' => 'This is dog'
        );
    }
}
