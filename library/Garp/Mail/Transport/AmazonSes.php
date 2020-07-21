<?php
/**
 * Amazon Simple Email Service (SES) connection object
 *
 * Integration between Zend Framework and Amazon Simple Email Service
 *
 * @package     Garp
 * @author      Martijn Gastkemper <martijn@grrr.nl>
 */
class Garp_Mail_Transport_AmazonSes extends Zend_Mail_Transport_Abstract
{
    const DEFAULT_REGION = 'eu-west-1';

    /**
     * @var \Aws\Ses\SesClient
     */
    protected $client;


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

        $region = isset($config['region']) ? $config['region'] : self::DEFAULT_REGION;

        $this->client = new \Aws\Ses\SesClient(
            [
                'version' => 'latest',
                'region' => $region,
                'credentials' => [
                    'key' => $config['accessKey'],
                    'secret' => $config['privateKey'],
                ],
            ]
        );
    }


    /**
     * Send an email using the amazon webservice api
     *
     * @return void
     */
    public function _sendMail()
    {
        $params = [
            'Source' => $this->_mail->getFrom(),
            'RawMessage' => [
                'Data' => sprintf("%s\n%s\n", $this->header, $this->body),
            ],
            'Destinations' => explode(',', $this->recipients),
        ];

        $this->client->sendRawEmail($params);
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

}
