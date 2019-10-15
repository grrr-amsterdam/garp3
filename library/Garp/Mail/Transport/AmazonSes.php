<?php
/**
 * Amazon Simple Email Service (SES) connection object
 *
 * Integration between Zend Framework and Amazon Simple Email Service
 *
 * @category    Zend
 * @package     Zend_Mail
 * @subpackage  Transport
 * @author      Christopher Valles <info@christophervalles.com>
 * @license     http://framework.zend.com/license/new-bsd New BSD License
 */
class Garp_Mail_Transport_AmazonSes extends Zend_Mail_Transport_Abstract
{
    const DEFAULT_REGION = 'eu-west-1';
    const HOST_TEMPLATE = 'https://email.%s.amazonaws.com';

    /**
     * Template of the webservice body request
     *
     * @var string
     */
    protected $_bodyRequestTemplate = 'Action=SendRawEmail&Source=%s&%s&RawMessage.Data=%s';


    /**
     * Remote smtp hostname or i.p.
     *
     * @var string
     */
    protected $_host;


    /**
     * Amazon Access Key
     *
     * @var string|null
     */
    protected $_accessKey;


    /**
     * Amazon private key
     *
     * @var string|null
     */
    protected $_privateKey;


    /**
     * Constructor.
     *
     * @param  array $config
     * @return void
     * @throws Zend_Mail_Transport_Exception if accessKey is not present in the config
     * @throws Zend_Mail_Transport_Exception if privateKey is not present in the config
     */
    public function __construct($config = array())
    {
        if ($config instanceof Zend_Config) {
            $config = $config->toArray();
        }
        if (!array_key_exists('accessKey', $config)) {
            throw new Zend_Mail_Transport_Exception('This transport requires the Amazon access key');
        }

        if (!array_key_exists('privateKey', $config)) {
            throw new Zend_Mail_Transport_Exception('This transport requires the Amazon private key');
        }

        if (!array_key_exists('host', $config)) {
            $config['host'] = sprintf(
                self::HOST_TEMPLATE,
                isset($config['region']) ? $config['region'] : self::DEFAULT_REGION
            );
        }

        $this->_accessKey = $config['accessKey'];
        $this->_privateKey = $config['privateKey'];
        $this->_host = Zend_Uri::factory($config['host']);
    }


    /**
     * Send an email using the amazon webservice api
     *
     * @return void
     */
    public function _sendMail()
    {
        $date = gmdate('D, d M Y H:i:s O');

        //Send the request
        $client = new Zend_Http_Client($this->_host);
        $client->setMethod(Zend_Http_Client::POST);
        $client->setHeaders(array(
            'Date' => $date,
            'X-Amzn-Authorization' => $this->_buildAuthKey($date)
        ));
        $client->setEncType('application/x-www-form-urlencoded');

        //Build the parameters
        $params = array(
            'Action' => 'SendRawEmail',
            'Source' => $this->_mail->getFrom(),
            'RawMessage.Data' => base64_encode(sprintf("%s\n%s\n", $this->header, $this->body))
        );

        $recipients = explode(',', $this->recipients);
        foreach ($recipients as $index => $recipient) {
            $params[sprintf('Destination.ToAddresses.member.%d', $index + 1)] = $recipient;
        }

        $client->resetParameters();
        $client->setParameterPost($params);
        $response = $client->request(Zend_Http_Client::POST);

        if ($response->getStatus() != 200) {
            throw new Exception($response->getBody());
        }
    }


    /**
     * Format and fix headers
     *
     * Some SMTP servers do not strip BCC headers. Most clients do it themselves as do we.
     *
     * @param   array $headers
     * @return  void
     * @throws  Zend_Transport_Exception
     */
    protected function _prepareHeaders($headers)
    {
        if (!$this->_mail) {
            /**
             * @see Zend_Mail_Transport_Exception
             */
            include_once 'Zend/Mail/Transport/Exception.php';
            throw new Zend_Mail_Transport_Exception('_prepareHeaders requires a registered Zend_Mail object');
        }

        unset($headers['Bcc']);

        // Prepare headers
        parent::_prepareHeaders($headers);
    }


    /**
     * Returns header string containing encoded authentication key
     *
     * @param   date $date
     * @return  string
     */
    private function _buildAuthKey($date){
        return sprintf('AWS3-HTTPS AWSAccessKeyId=%s,Algorithm=HmacSHA256,Signature=%s', $this->_accessKey, base64_encode(hash_hmac('sha256', $date, $this->_privateKey, true)));
    }
}
