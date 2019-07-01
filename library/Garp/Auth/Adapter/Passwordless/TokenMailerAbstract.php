<?php

/**
 * Garp_Auth_Adapter_Passwordless_TokenMailerAbstract
 *
 * @package Subsidieportaal
 * @author  Kevin Rombout <kevin@grrr.nl>
 */
abstract class Garp_Auth_Adapter_Passwordless_TokenMailerAbstract implements Garp_Auth_Adapter_Passwordless_TokenMailerInterface {

    public function send() {
        $mailer = new Garp_Mailer();
        return $mailer->send(
            [
                'to' => $this->_getEmailTo(),
                'subject' => $this->_getSubject(),
                $this->_getMessageKey() => $this->_getMessage()
            ]
        );
    }

    abstract protected function _getEmailTo(): string;

    abstract protected function _getSubject(): string;

    abstract protected function _getMessage(): string;

    protected function _getSnippet($identifier) {
        $snippetModel = new Model_Snippet();
        if ($snippetModel->isMultilingual()) {
            $snippetModel = (new Garp_I18n_ModelFactory)->getModel('Snippet');
        }
        return $snippetModel->fetchByIdentifier($identifier);
    }

    protected function _getMessageKey(): string {
        return 'message';
    }

    protected function _interpolateEmailBody($body) {
        return Garp_Util_String::interpolate(
            $body, array(
                'LOGIN_URL' => $this->_getLoginUrl()
            )
        );
    }

    protected function _getLoginUrl() {
        return new Garp_Util_FullUrl(array(array('method' => 'passwordless'), 'auth_submit')) .
            '?uid=' . $this->_userId . '&token=' . $this->_token;
    }
}
