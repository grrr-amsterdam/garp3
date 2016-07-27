<?php
/**
 * Garp_Mailer
 * Central mail singleton that you don't have to configure every time you want to send a little
 * message.
 *
 * @package      Garp
 * @author       Harmen Janssen <harmen@grrr.nl>
 * @version      0.1.0
 */
class Garp_Mailer {
    const EXCEPTION_CANNOT_RESOLVE_FROM_ADDRESS = 'Unable to find from address.';

    protected $_transport;
    protected $_fromAddress;
    protected $_fromName;
    protected $_characterEncoding = 'utf-8';
    protected $_attachments = array();
    protected $_htmlView;
    protected $_htmlViewModule;

    public function __construct() {
        $this->addAttachments($this->getDefaultAttachments());
        $this->setHtmlTemplate($this->getDefaultHtmlTemplate());
        $this->setHtmlTemplateModule($this->getDefaultHtmlTemplateModule());
    }

    /**
     * Send an email.
     *
     * @param Array $params Config object.
     *  Required keys: to, subject, message
     *  Optional keys: replyTo
     * @param Array $viewParams Any values you wish to send to the HTML mail template
     * @param Array $attachments
     * @return Boolean
     */
    public function send(array $params, array $viewParams = array(), $attachments = array()) {
        $this->_validateParams($params);

        $mail = new Zend_Mail($this->getCharacterEncoding());
        $mail->setSubject($params['subject']);
        $mail->setBodyText($this->_getPlainBodyText($params));
        $mail->setFrom($this->getFromAddress(), $this->getFromName());
        $mail->addTo($params['to']);

        if ($this->getHtmlTemplate()) {
            $viewParams['message'] = isset($params['message']) ? $params['message'] : '';
            $viewParams['htmlMessage'] = isset($params['htmlMessage']) 
                ? $params['htmlMessage'] : '';
            $mail->setBodyHtml($this->_renderView($viewParams));
        } elseif (isset($params['htmlMessage'])) {
            $mail->setBodyHtml($params['htmlMessage']);
        }

        if (!empty($params['replyTo'])) {
            $mail->setReplyTo($params['replyTo']);
        }

        if (!empty($params['cc'])) {
            $mail->addCc($params['cc']);
        }

        if (!empty($params['bcc'])) {
            $mail->addBcc($params['bcc']);
        }

        $this->addAttachments($attachments);
        $mimeParts = array_map(array($this, '_attachmentToMimePart'), $this->_attachments);
        array_walk($mimeParts, array($mail, 'addAttachment'));

        return $mail->send($this->getTransport());
    }

    public function setTransport(Zend_Mail_Transport_Abstract $transport) {
        $this->_transport = $transport;
        return $this;
    }

    public function getTransport() {
        if (!$this->_transport) {
            $this->setTransport($this->getDefaultTransport());
        }
        return $this->_transport;
    }

    public function getDefaultTransport() {
        if ($this->_isMailingDisabled()) {
            return new Zend_Mail_Transport_File();
        }
        if ($this->_isAmazonSesConfigured()) {
            return new Garp_Mail_Transport_AmazonSes(
                $this->_configureAmazonSesTransport()
            );
        }
        return new Zend_Mail_Transport_Sendmail();
    }

    public function setFromAddress($fromAddress) {
        $this->_fromAddress = $fromAddress;
        return $this;
    }

    public function getFromAddress() {
        if (!$this->_fromAddress) {
            $this->setFromAddress($this->getDefaultFromAddress());
        }
        return $this->_fromAddress;
    }

    public function setFromName($fromName) {
        $this->_fromName = $fromName;
        return $this;
    }

    public function getFromName() {
        return $this->_fromName ?: null;
    }

    public function getDefaultFromAddress() {
        $config = Zend_Registry::get('config');
        if ($this->_isAmazonSesConfigured() 
            && isset($this->_getAmazonSesConfiguration()->fromAddress)
        ) {
            return $this->_getAmazonSesConfiguration()->fromAddress;
        }
        if (isset($config->mailer->fromAddress)) {
            return $config->mailer->fromAddress;
        }
        throw new Garp_Mailer_Exception_CannotResolveFromAddress(
            self::EXCEPTION_CANNOT_RESOLVE_FROM_ADDRESS
        );
    }

    /**
     * Set character encoding
     *
     * @param String $characterEncoding
     * @return $this
     */
    public function setCharacterEncoding($characterEncoding) {
        $this->_characterEncoding = $characterEncoding;
        return $this;
    }

    /**
     * Get character encoding
     *
     * @return String
     */
    public function getCharacterEncoding() {
        return $this->_characterEncoding;
    }

    /**
     * Read default attachments from config. Handy if you use images in your HTML template that are
     * in every mail.
     *
     * @return Array
     */
    public function getDefaultAttachments() {
        $config = Zend_Registry::get('config');
        if (!isset($config->mailer->attachments)) {
            return array();
        }
        return $config->mailer->attachments->toArray();
    }

    public function addAttachments(array $attachments) {
        foreach ($attachments as $id => $attachment) {
            $this->addAttachment($id, $attachment);
        }
    }

    public function addAttachment($id, $attachment) {
        $this->_attachments[] = array($id, $attachment);
    }

    public function getDefaultHtmlTemplate() {
        return isset(Zend_Registry::get('config')->mailer->template) ?
            Zend_Registry::get('config')->mailer->template : null;
    }

    public function setHtmlTemplate($template) {
        $this->_htmlView = $template;
    }

    public function getHtmlTemplate() {
        return $this->_htmlView;
    }

    public function getDefaultHtmlTemplateModule() {
        return isset(Zend_Registry::get('config')->mailer->template_module) ?
            Zend_Registry::get('config')->mailer->template_module : 'default';
    }

    public function setHtmlTemplateModule($module) {
        $this->_htmlViewModule = $module;
    }

    public function getHtmlTemplateModule() {
        return $this->_htmlViewModule;
    }

    protected function _renderView($viewParams) {
        $viewObj = Zend_Controller_Front::getInstance()->getParam('bootstrap')
            ->getResource('view');

        $module = $this->getHtmlTemplateModule();
        if ($module) {
            $moduleDirectory = Zend_Controller_Front::getInstance()
            ->getModuleDirectory($module);
            $viewPath = $moduleDirectory.'/views/scripts/';
            $viewObj->addScriptPath($viewPath);
        }

        $viewObj->assign($viewParams);
        return $viewObj->render($this->getHtmlTemplate());
    }

    protected function _getPlainBodyText($params) {
        if (isset($params['message'])) {
            return $params['message'];
        }
        if (isset($params['htmlMessage'])) {
            return strip_tags($params['htmlMessage']);
        }
        return null;
    }

    /**
     * Required keys: to, subject, message OR htmlMessage
     *
     * @param array $params 
     * @return void
     */
    protected function _validateParams(array $params) {
        $config = new Garp_Util_Configuration($params);
        $config->obligate('to');
        $config->obligate('subject');

        if (!isset($params['message']) && !isset($params['htmlMessage'])) {
            throw new Garp_Util_Configuration_Exception(
                sprintf(
                    Garp_Util_Configuration::EXCEPTION_MISSINGKEY,
                    'message OR htmlMessage'
                )
            );
        }
    }

    protected function _attachmentToMimePart($args) {
        list($id, $attachment) = $args;
        $obj = file_get_contents($attachment);
        // Check if the attachment is gzipped and act accordingly
        $unpacked = @gzdecode($obj);
        $obj = null !== $unpacked && false !== $unpacked ? $unpacked : $obj;

        $finfo = new finfo(FILEINFO_MIME);
        $mime  = $finfo->buffer($obj);

        $at = new Zend_Mime_Part($obj);
        $at->id = $id;
        $at->type = $mime;
        /**
         * @todo Allow disposition attachment
         */
        $at->disposition = Zend_Mime::DISPOSITION_INLINE;
        $at->encoding = Zend_Mime::ENCODING_BASE64;
        $at->filename = basename($attachment);
        return $at;
    }

    /**
     * For legacy reasons, mailing can be disabled both thru the mailer key or thru amazon ses
     * configuration.
     *
     * @return void
     */
    protected function _isMailingDisabled() {
        $config = Zend_Registry::get('config');
        return (isset($config->mailer->sendMail) && !$config->mailer->sendMail) ||
            ($this->_isAmazonSesConfigured() &&
                (isset($config->amazon->ses->sendMail) && !$config->amazon->ses->sendMail));
    }

    protected function _isAmazonSesConfigured() {
        return isset(Zend_Registry::get('config')->amazon->ses->accessKey);
    }

    protected function _configureAmazonSesTransport() {
        $sesConfig = $this->_getAmazonSesConfiguration();
        return array(
            'accessKey'  => $sesConfig->accessKey,
            'privateKey' => $sesConfig->secretKey,
            'region'     => $sesConfig->region
        );
    }

    protected function _getAmazonSesConfiguration() {
        return Zend_Registry::get('config')->amazon->ses;
    }

}
