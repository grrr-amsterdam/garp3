<?php

use Garp\Functional as f;

/**
 * Garp_Service_Amazon_Ses
 * Wrapper around Amazon Simple Email Service
 *
 * @package Garp
 * @author Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Service_Amazon_Ses extends Zend_Service_Amazon_Abstract
{

    /**
     * Default region
     *
     * @var string
     */
    const DEFAULT_REGION = 'us-east-1';

    /**
     * Region to use in the endpoint
     */
    protected $_region;

    /**
     * @var \Aws\Ses\SesClient
     */
    protected $client;

    /**
     * Create Amazon client.
     *
     * @param string $accessKey Override the default Access Key
     * @param string $secretKey Override the default Secret Key
     * @param string $region Override the default Region
     * @return void
     */
    public function __construct($accessKey = null, $secretKey = null, $region = null)
    {
        $ini = Zend_Registry::get('config');
        if (!$accessKey || !$secretKey) {
            if (!$accessKey && isset($ini->amazon->ses->accessKey)) {
                $accessKey = $ini->amazon->ses->accessKey;
            }
            if (!$secretKey && isset($ini->amazon->ses->secretKey)) {
                $secretKey = $ini->amazon->ses->secretKey;
            }
        }
        if (!$region) {
            $region = isset($ini->amazon->ses->region) ?
                $ini->amazon->ses->region : self::DEFAULT_REGION;
        }
        $this->setRegion($region);
        parent::__construct($accessKey, $secretKey);

        $this->client = new \Aws\Ses\SesClient(
            [
                'version' => 'latest',
                'region' => $this->getRegion(),
                'credentials' => [
                    'key' => $this->_getAccessKey(),
                    'secret' => $this->_getSecretKey(),
                ],
            ]
        );
    }

    /**
     * Get region
     *
     * @return string
     */
    public function getRegion()
    {
        return $this->_region;
    }

    /**
     * Set region
     *
     * @param string $region
     * @return $this
     */
    public function setRegion($region)
    {
        $this->_region = $region;
        return $this;
    }

    /**
     * AWS Action mappers
     * -----------------------------------------------------------------------------------------------
     */

    /**
     * Deletes the specified email address from the list of verified addresses.
     *
     * @param string $email An email address to be removed from the list of verified addreses.
     * @return boolean
     */
    public function deleteVerifiedEmailAddress($email)
    {
        $this->client->deleteVerifiedEmailAddress([
            'EmailAddress' => $email,
        ]);
        return true;
    }

    /**
     * Returns the user's current activity limits.
     *
     * @return array Describing various statistics about your usage. The keys correspond to
     *               nodes in Amazon's response
     */
    public function getSendQuota()
    {
        $response = $this->client->getSendQuota();
        return [
            'Max24HourSend' => $response->get('Max24HourSend'),
            'MaxSendRate' => $response->get('MaxSendRate'),
            'SentLast24Hours' => $response->get('SentLast24Hours'),
        ];
    }

    /**
     * Returns a list containing all of the email addresses that have been verified.
     *
     * @return array
     */
    public function listVerifiedEmailAddresses()
    {
        $response = $this->client->listVerifiedEmailAddresses();
        return $response->get('VerifiedEmailAddresses');
    }

    /**
     * Composes an email message based on input data, and then immediately queues the message for sending.
     *
     * @phpcs:disable
     * @param array|Garp_Util_Configuration $args Might contain;
     *  ['Destination']     [required]  array|string    Email address(es) for To field, or if array, it might contain;
     *      ['To']          [required]  array|string    Email address(es) for To field
     *      ['Cc']          [optional]  array|string    Email address(es) for Cc field
     *      ['Bcc']         [optional]  array|string    Email address(es) for Bcc field
     *  ['Message']         [required]  array|string    The email message (Text format), or if array, it might contain;
     *      ['Html']        [optional]  array|string    HTML content for the message, or if array, it might contain;
     *          ['Charset'] [optional]  string          The characterset used for the HTML body
     *          ['Data']    [required]  string          The actual content of the HTML body
     *      ['Text']        [optional]  array|string    Textual content for the message, or if array, it might contain;
     *          ['Charset]  [optional]  string          The characterset used for the textual body
     *          ['Data']    [required]  string          The actual content of the textual body
     *  ['Subject']         [required]  array|string    The email subject, or if string, it might contain;
     *      ['Charset']     [optional]  string          The characterset used for the subject
     *      ['Data']        [required]  string          The actual subject
     *  ['Source']          [required]  string          The sender's email address
     *  ['ReplyToAddresses'][optional]  array|string    The reply-to email address(es) for the message
     *  ['ReturnPath']      [optional]  string          The email address to which bounce notifications are to be forwarded.
     *                                                  If the message cannot be delivered to the recipient, then an
     *                                                  error message will be returned from the recipient's ISP; this message
     *                                                  will then be forwarded to the email address specified by the ReturnPath parameter.
     * @phpcs:enable
     *
     * @deprecated Use Garp_Mailer->send();
     * @return bool
     */
    public function sendEmail($args)
    {
        $args = $args instanceof Garp_Util_Configuration ? $args : new Garp_Util_Configuration($args);
        $args->obligate('Destination')->obligate('Message')->obligate('Subject')->obligate('Source');
        $args = (array)$args;

        // Allow global overriding of the To property to funnel emails only to a safe address
        if (isset(Zend_Registry::get('config')->amazon->ses->forceToAddress)) {
            $args['Destination'] = [
                'To' =>
                    Zend_Registry::get('config')->amazon->ses->forceToAddress,
            ];
        }

        if (is_array($args['Message'])) {
            if (!array_key_exists('Html', $args['Message']) && !array_key_exists('Text', $args['Message'])) {
                throw new Garp_Service_Amazon_Exception('Either Text or Html is required for Message.');
            } else {
                if (!empty($args['Message']['Html'])) {
                    if (is_array($args['Message']['Html'])) {
                        if (empty($args['Message']['Html']['Data'])) {
                            throw new Garp_Service_Amazon_Exception('Data is a required key for Html.');
                        } else {
                            $message = $args['Message'];
                        }
                    } else {
                        $message = ['Html' => ['Data' => $args['Message']['Html']]];
                    }
                }
                if (!empty($args['Message']['Text'])) {
                    if (is_array($args['Message']['Text'])) {
                        if (empty($args['Message']['Text']['Data'])) {
                            throw new Garp_Service_Amazon_Exception('Data is a required key for Text.');
                        } else {
                            $message = $args['Text'];
                        }
                    } else {
                        $message = ['Text' => ['Data' => $args['Message']['Text']]];
                    }
                }
            }
        } else {
            $message = ['Text' => ['Data' => $args['Message']]];
        }
        $args['Message'] = ['Body' => $message];

        if (is_array($args['Subject'])) {
            if (empty($args['Subject']['Data'])) {
                throw new Garp_Service_Amazon_Exception('Data is a required key for Subject.');
            } else {
                $subject = $args['Subject'];
            }
        } else {
            $subject = ['Data' => $args['Subject']];
        }
        $args['Message']['Subject'] = $subject;
        unset($args['Subject']);

        if (is_array($args['Destination'])) {
            if (empty($args['Destination']['To'])) {
                throw new Garp_Service_Amazon_Exception('To is a required key for Destination');
            } else {
                $renameKeys = f\rename_keys(['To' => 'ToAddresses', 'Cc' => 'CcAddresses', 'Bcc' => 'BccAddresses']);
                $destination = $renameKeys($args['Destination']);

                $toArray = function ($mixed) {
                    return (array)$mixed;
                };
                $destination = f\map($toArray, $destination);
            }
        } else {
            $destination = (array)$args['Destination'];
        }
        $args['Destination'] = $destination;

        $this->client->sendEmail($args);
        return true;
    }

    /**
     * Sends an email message, with header and content specified by the client. The SendRawEmail action is useful for sending multipart MIME emails.
     * The raw text of the message must comply with Internet email standards; otherwise, the message cannot be sent.
     *
     * @phpcs:disable
     * @param array|Garp_Util_Configuration $args Might contain;
     *  ['RawMessage']      [required]  string  The raw email message (headers and body)
     *  ['Source']          [optional]  string  A FROM address
     *  ['Destinations']    [optional]  array   Email addresses of recipients (optional because TO fields may be present in raw message)
     * @phpcs:enable
     *
     * @return Boolean
     * @deprecated Use Garp_Mailer->sendMail()
     */
    public function sendRawEmail($args)
    {
        $this->client->sendRawEmail($args);
        return true;
    }

    /**
     * Verifies an email address.
     * This action causes a confirmation email message to be sent to the specified address.
     *
     * @param string $email The email address to be verified.
     * @return string
     */
    public function verifyEmailAddress($email)
    {
        $this->client->verifyEmailIdentity([
            'EmailAddress' => $email
        ]);

        return true;
    }
}
