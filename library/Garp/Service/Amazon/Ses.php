<?php
/**
 * Garp_Service_Amazon_Ses
 * Wrapper around Amazon Simple Email Service
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Amazon
 * @lastmodified $Date: $
 */
class Garp_Service_Amazon_Ses extends Zend_Service_Amazon_Abstract {
	/**
	 * Amazon web service url
	 * @var String
	 */
	const ENDPOINT = 'https://email.us-east-1.amazonaws.com/';
	
	
	/**
	 * What hash method to use when creating the signature
	 * @var String
	 */
	const SIGNATURE_HASH_METHOD = 'sha256';
	
	
	/**
	 * SES API version
	 * @var String
	 */
	const API_VERSION = '2010-12-01';
	
	
	/**
     * Create Amazon client.
     *
     * @param  string $access_key       Override the default Access Key
     * @param  string $secret_key       Override the default Secret Key
     * @return void
     */
	public function __construct($accessKey = null, $secretKey = null) {
		if (!$accessKey || !$secretKey) {
			$ini = Garp_Cache_Ini::factory(APPLICATION_PATH.'/configs/application.ini', APPLICATION_ENV);
			if (!$accessKey && isset($ini->amazon->ses->accessKey)) {
				$accessKey = $ini->amazon->ses->accessKey;
			}
			if (!$secretKey && isset($ini->amazon->ses->secretKey)) {
				$secretKey = $ini->amazon->ses->secretKey;
			}
		}		
		parent::__construct($accessKey, $secretKey);
	}
	
	
	/**
	 * AWS Action mappers
	 * -----------------------------------------------------------------------------------------------
	 */
	
	
	/**
	 * Deletes the specified email address from the list of verified addresses.
	 * @param String $email An email address to be removed from the list of verified addreses.
	 * @return Boolean
	 */
	public function deleteVerifiedEmailAddress($email) {
		$response = $this->_makeRequest(array(
			'Action' => 'DeleteVerifiedEmailAddress',
			'EmailAddress' => $email
		));
		return true;
	}
	
	
	/**
	 * Returns the user's current activity limits.
	 * @return Array Describing various statistics about your usage. The keys correspond to 
	 * 				 nodes in Amazon's response
	 */
	public function getSendQuota() {
		$response = $this->_makeRequest(array(
			'Action' => 'GetSendQuota'
		));
		$dom = new DOMDocument();
		$dom->loadXML($response);
		$out = array(
			'Max24HourSend' 	=> $dom->getElementsByTagName('Max24HourSend')->item(0)->nodeValue,
			'MaxSendRate'		=> $dom->getElementsByTagName('MaxSendRate')->item(0)->nodeValue,
			'SentLast24Hours'	=> $dom->getElementsByTagName('SentLast24Hours')->item(0)->nodeValue
		);
		return $out;
	}
	
	
	/**
	 * Returns the user's sending statistics. The result is a list of data points, representing the last two weeks of sending activity.
	 * Each data point in the list contains statistics for a 15-minute interval.
	 * @return Array
	 */
	public function getSendStatistics() {
		$response = $this->_makeRequest(array(
			'Action' => 'GetSendStatistics'
		));
		$dom = new DOMDocument();
		$dom->loadXML($response);
		$members = $dom->getElementsByTagName('member');
		$out = array();
		foreach ($members as $member) {
			$out[] = array(
				'DeliveryAttempts'	=> $member->getElementsByTagName('DeliveryAttempts')->item(0)->nodeValue,
				'Timestamp'			=> $member->getElementsByTagName('Timestamp')->item(0)->nodeValue,
				'Rejects'			=> $member->getElementsByTagName('Rejects')->item(0)->nodeValue,
				'Bounces'			=> $member->getElementsByTagName('Bounces')->item(0)->nodeValue,
				'Complaints'		=> $member->getElementsByTagName('Complaints')->item(0)->nodeValue,
			);
		}
		return $out;
	}
	
	
	/**
	 * Returns a list containing all of the email addresses that have been verified.
	 * @return Array
	 */
	public function listVerifiedEmailAddresses() {
		$response = $this->_makeRequest(array(
			'Action' => 'ListVerifiedEmailAddresses'
		));
		$dom = new DOMDocument();
		$dom->loadXML($response);
		$members = $dom->getElementsByTagName('member');
		$out = array();
		foreach ($members as $member) {
			$out[] = $member->nodeValue;
		}		
		return $out;
	}
	
	
	/**
	 * Composes an email message based on input data, and then immediately queues the message for sending.
	 * @param Array|Garp_Util_Configuration $args Might contain;
	 * 	['Destination']		[required]	Array|String	Email address(es) for To field, or if array, it might contain;
	 * 		['To']			[required]	Array|String	Email address(es) for To field
	 * 		['Cc']			[optional]	Array|String	Email address(es) for Cc field
	 * 		['Bcc']			[optional]	Array|String	Email address(es) for Bcc field
	 * 	['Message']			[required]	Array|String	The email message (Text format), or if array, it might contain;
	 * 		['Html']		[optional]	Array|String	HTML content for the message, or if array, it might contain;
	 * 			['Charset']	[optional]	String			The characterset used for the HTML body
	 * 			['Data']	[required]	String			The actual content of the HTML body
	 * 		['Text']		[optional]	Array|String	Textual content for the message, or if array, it might contain;
	 * 			['Charset]	[optional]	String			The characterset used for the textual body
	 * 			['Data']	[required]	String			The actual content of the textual body
	 * 	['Subject']			[required]	Array|String	The email subject, or if string, it might contain;
	 * 		['Charset']		[optional]	String			The characterset used for the subject
	 * 		['Data']		[required]	String			The actual subject
	 * 	['Source']			[required]	String			The sender's email address
	 * 	['ReplyToAddresses'][optional]	Array|String	The reply-to email address(es) for the message
	 * 	['ReturnPath']		[optional]	String			The email address to which bounce notifications are to be forwarded.
	 * 													If the message cannot be delivered to the recipient, then an 
	 * 													error message will be returned from the recipient's ISP; this message 
	 * 													will then be forwarded to the email address specified by the ReturnPath parameter.
	 * @return String
	 */
	public function sendEmail($args) {
		$args = $args instanceof Garp_Util_Configuration ? $args : new Garp_Util_Configuration($args);
		$args['Action'] = 'SendEmail';
		$args->obligate('Destination')->obligate('Message')->obligate('Subject')->obligate('Source');
		$args = (array)$args;
		
		if (is_array($args['Message'])) {
			if (!array_key_exists('Html', $args['Message']) &&
				!array_key_exists('Text', $args['Message'])) {
				throw new Garp_Service_Amazon_Exception('Either Text or Html is required for Message.');
			} else {
				if (!empty($args['Message']['Html'])) {
					if (is_array($args['Message']['Html'])) {
						if (empty($args['Message']['Html']['Data'])) {
							throw new Garp_Service_Amazon_Exception('Data is a required key for Html.');
						} else {
							$args['Message.Body.Html.Data'] = $args['Message']['Html']['Data'];
							if (!empty($args['Message']['Html']['Charset'])) {
								$args['Message.Body.Html.Charset'] = $args['Message']['Html']['Charset'];
							}
						}
					} else {
						$args['Message.Body.Html.Data'] = $args['Message']['Html'];
					}
					unset($args['Message']['Html']);
				}
				if (!empty($args['Message']['Text'])) {
					if (is_array($args['Message']['Text'])) {
						if (empty($args['Message']['Text']['Data'])) {
							throw new Garp_Service_Amazon_Exception('Data is a required key for Text.');
						} else {
							$args['Message.Body.Text.Data'] = $args['Message']['Text']['Data'];
							if (!empty($args['Message']['Text']['Charset'])) {
								$args['Message.Body.Text.Charset'] = $args['Message']['Text']['Charset'];
							}
						}
					} else {
						$args['Message.Body.Text.Data'] = $args['Message']['Text'];
					}
					unset($args['Message']['Text']);
				}
			}
		} else {
			$args['Message.Body.Text.Data'] = $args['Message'];
		}
		unset($args['Message']);

		if (is_array($args['Subject'])) {
			if (empty($args['Subject']['Data'])) {
				throw new Garp_Service_Amazon_Exception('Data is a required key for Subject.');
			} else {
				$args['Message.Subject.Data'] = $args['Subject']['Data'];
				if (!empty($args['Subject']['Charset'])) {
					$args['Message.Subject.Charset'] = $args['Subject']['Charset'];
				}
			}
		} else {
			$args['Message.Subject.Data'] = $args['Subject'];
		}
		unset($args['Subject']);
		
		if (is_array($args['Destination'])) {
			if (empty($args['Destination']['To'])) {
				throw new Garp_Service_Amazon_Exception('To is a required key for Destination');
			} else {
				$tos = $this->_arrayToStringList((array)$args['Destination']['To'], 'Destination.ToAddresses');
				$args += $tos;
				if (!empty($args['Destination']['Cc'])) {
					$ccs = $this->_arrayToStringList((array)$args['Destination']['Cc'], 'Destination.CcAddresses');
					$args += $ccs;
				}
				if (!empty($args['Destination']['Bcc'])) {
					$bccs = $this->_arrayToStringList((array)$args['Destination']['Bcc'], 'Destination.BccAddresses');
					$args += $bccs;
				}
			}
		} else {
			$to = $this->_arrayToStringList((array)$args['Destination'], 'Destination.ToAddresses');
			$args += $to;
		}
		unset($args['Destination']);

		if (!empty($args['ReplyToAddresses'])) {
			$replyTos = $this->_arrayToStringList((array)$args['ReplyToAddresses'], 'ReplyToAddresses');
			$args += $replyTos;
			unset($args['ReplyToAddresses']);
		}

		$response = $this->_makeRequest((array)$args);		
		return true;
	}
	
	
	/**
	 * Sends an email message, with header and content specified by the client. The SendRawEmail action is useful for sending multipart MIME emails. 
	 * The raw text of the message must comply with Internet email standards; otherwise, the message cannot be sent.
	 * @param Array|Garp_Util_Configuration $args Might contain;
	 *  ['RawMessage']		[required]	String	The raw email message (headers and body)
	 *  ['Source']			[optional]	String	A FROM address
	 * 	['Destinations']	[optional]	Array	Email addresses of recipients (optional because TO fields may be present in raw message)
	 * @return Boolean
	 */
	public function sendRawEmail($args) {
		$args = $args instanceof Garp_Util_Configuration ? $args : new Garp_Util_Configuration($args);
		$args['Action'] = 'SendRawEmail';
		$args->obligate('RawMessage');
		$args = (array)$args;
		
		// normalize so-called "String List" parameters
		if (!empty($args['Destinations'])) {
			$destinations = $this->_arrayToStringList((array)$args['Destinations'], 'Destinations');
			$args += $destinations;
			unset($args['Destinations']);
		}
		$args['RawMessage.Data'] = $args['RawMessage'];
		unset($args['RawMessage']);
		
		$response = $this->_makeRequest((array)$args);
		return true;
	}
	
	
	/**
	 * Verifies an email address. 
	 * This action causes a confirmation email message to be sent to the specified address.
	 * @param String $email The email address to be verified.
	 * @return String
	 */
	public function verifyEmailAddress($email) {
		$response = $this->_makeRequest(array(
			'Action' => 'VerifyEmailAddress',
			'EmailAddress' => $email
		));
		return true;
	}
	
	
	/**
	 * Makes the actual AWS request.
	 * @param String $method
	 * @param Array $args
	 * @return Mixed
	 */
	protected function _makeRequest($args = array()) {
		$date = date(DATE_RFC1123);
		$sig  = $this->_createSignature($date);
		$amznAuthHeader = 'AWS3-HTTPS '.
							'AWSAccessKeyId='.$this->_accessKey.
							', Algorithm=Hmac'.strtoupper(self::SIGNATURE_HASH_METHOD).
							', Signature='.$sig;
		
		$client = $this->getHttpClient();
		$client->setUri(self::ENDPOINT);
		$client->setHeaders(array(
			'Date' => $date,
			'X-Amzn-Authorization' => $amznAuthHeader
		));
		
		// required parameters for each request
		$args['Signature'] = $sig;
		$args['SignatureMethod'] = 'Hmac'.self::SIGNATURE_HASH_METHOD;
		$args['SignatureVersion'] = 2;
		$args['Version'] = self::API_VERSION;
		
		$client->setParameterPost($args);
		$response = $client->request(Zend_Http_Client::POST);

		if ($response->getStatus() !== 200) {
			$this->throwException($response->getBody());
		}		
		return $response->getBody();
	}
	
	
	/**
	 * Create the HMAC-SHA signature required for every request.
	 * @return String
	 */
	protected function _createSignature($date) {
		$sig = Zend_Crypt_Hmac::compute($this->_secretKey, self::SIGNATURE_HASH_METHOD, $date, Zend_Crypt_Hmac::BINARY);
		return base64_encode($sig);
	}
	
	
	/**
	 * Create AWS String List from array (which is also an array, but with 
	 * specific Amazonesque keys)
	 * @param Array $array
	 * @param String $namespace The base namespace that groups all entries
	 * @return Array
	 */
	protected function _arrayToStringList(array $args, $namespace) {
		$out = array();
		foreach ($args as $key => $value) {
			$out[$namespace.'.member.'.($key+1)] = $value;
		}
		return $out;
	}
	
	
	/**
	 * Throw exceptions
	 * @param String $body XML body containing error response from AWS
	 * @return Void
	 * @throws Garp_Service_Amazon_Exception
	 */
	public function throwException($body) {
		$dom = new DOMDocument();
		$dom->loadXML($body);
		$code = $dom->getElementsByTagName('Code')->item(0);
		$msg  = $dom->getElementsByTagName('Message')->item(0);

		// Log last request and response in case of an error
		$lastRequest = $this->getHttpClient()->getLastRequest();
		$lastResponse = $this->getHttpClient()->getLastResponse();
		$filename = date('Y-m-d').'-amazon-ses.log';
		$logMessage  = "\n";
		$logMessage .= '[REQUEST]'."\n";
		$logMessage .= $lastRequest."\n\n";
		$logMessage .= '[RESPONSE]'."\n";
		$logMessage .= $lastResponse."\n\n";

		dump($filename, $logMessage);

		throw new Garp_Service_Amazon_Exception($code->nodeValue.': '.$msg->nodeValue);
	}
}
