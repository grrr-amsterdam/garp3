<?php

/**
 * Garp_Auth_Adapter_Passwordless_TokenMailer
 *
 * @package Subsidieportaal
 * @author  Kevin Rombout <kevin@grrr.nl>
 */
class Garp_Auth_Adapter_Passwordless_TokenMailer extends Garp_Auth_Adapter_Passwordless_TokenMailerAbstract {

    protected $_email;
    protected $_userId;

    protected $_token;
    protected $_authVars;

    public function __construct($email, $userId, $token, $authVars) {
        $this->_email    = $email;
        $this->_userId   = $userId;
        $this->_token    = $token;
        $this->_authVars = $authVars;
    }

    protected function _getEmailTo(): string {
        return $this->_email;
    }

    protected function _getSubject(): string {
        return $this->_getEmailSubject();
    }

    protected function _getMessage(): string {
        return $this->_getEmailBody();
    }

    protected function _getEmailBody() {
        if (!empty($this->_authVars->email_body_snippet_identifier)
            && $this->_authVars->email_body_snippet_identifier
        ) {
            return $this->_interpolateEmailBody(
                $this->_getSnippet($this->_authVars->email_body_snippet_identifier)->text, $this->_userId, $this->_token
            );
        }
        if (!empty($this->_authVars->email_body)) {
            return $this->_interpolateEmailBody($this->_authVars->email_body, $this->_userId, $this->_token);
        }

        throw new Garp_Auth_Adapter_Passwordless_Exception(
            'Missing email body: configure a ' .
            'snippet or hard-code a string.'
        );
    }

    protected function _getEmailSubject() {
        if (isset($this->_authVars->email_subject_snippet_identifier)
            && $this->_authVars->email_subject_snippet_identifier
        ) {
            return $this->_getSnippet($this->_authVars->email_subject_snippet_identifier)->text;
        }
        if (isset($this->_authVars->email_subject) && $this->_authVars->email_subject) {
            return $this->_authVars->email_subject;
        }

        throw new Garp_Auth_Adapter_Passwordless_Exception(
            'Missing email subject: configure a ' .
            'snippet or hard-code a string.'
        );

    }

    protected function _getMessageKey(): string {
        return 'message';
    }
}
