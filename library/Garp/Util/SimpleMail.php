<?php
/**
 * Garp_Util_SimpleMail
 * Sends simple mails to clients. Mails that come down to a list of the 
 * filled out form fields. 
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class Garp_Util_SimpleMail {
	/**
	 * Newline char
	 * @var String
	 */
	const NEWLINE = "\r\n";


	/**
	 * Honeypot key in the form
	 * @var String
	 */
	const HONEYPOT_KEY = 'hp';
	
	
	/**
	 * Timestamp key in the form
	 * @var String
	 */
	const TIMESTAMP_KEY = 'ts';


	/**
	 * The actual Mail object
	 * @var Zend_Mail
	 */
	protected $_mail;
	
	
	/**
	 * Various parameters concerning the email
	 * @var Array
	 */
	protected $_params = array();
	
	
	/**
	 * Submitted post values
	 * @var Array
	 */
	protected $_postParams = array();
	
	
	/**
	 * Skippable keys, don't add these to the list.
	 * @var Array
	 */
	protected $_skippableKeys = array('controller', 'module', 'action', 'locale', 'ts', 'hp');
	
	
	/**
	 * Errors
	 * @var Array
	 */
	protected $_errors = array();
	
	
	/**
	 * Use aliases for keys
	 * @var Array
	 */
	protected $_aliases = array();
	
	
	/**
	 * Class constructor
	 * @param Array $postParams 
	 * @return Void
	 */
	public function __construct(array $postParams) {
		$this->_mail = new Zend_Mail();
		$this->_postParams = $postParams;
	}
	
	
	/**
	 * Set body text
	 * @param String $body
	 * @return $this
	 */
	public function setBodyText($body) {
		$this->_params['body'] = $body;
		return $this;
	}
	
	
	/**
	 * Get body text
	 * @return String
	 */
	public function getBodyText() {
		return !empty($this->_params['body']) ? $this->_params['body'] : '';
	}
	
	
	/**
	 * Set subject
	 * @param String $subject
	 * @return $this
	 */
	public function setSubject($subject) {
		$this->_params['subject'] = $subject;
		return $this;
	}
	
	
	/**
	 * Get subject
	 * @return String
	 */
	public function getSubject() {
		return !empty($this->_params['subject']) ? $this->_params['subject'] : '';
	}
	
	
	/**
	 * Set from address
	 * @param Mixed $from
	 * @return $this
	 */
	public function setFrom($from) {
		if (!is_array($from)) {
			$from = array($from, '');
		}
		$this->_params['from'] = $from;
		return $this;
	}
	
	
	/**
	 * Get from address
	 * @return String
	 */
	public function getFrom() {
		return !empty($this->_params['from']) ? $this->_params['from'] : '';
	}
	
	
	/**
	 * Set to address
	 * @param Mixed $to 
	 * @return $this
	 */
	public function setTo($to) {
		if (!is_array($to)) {
			$to = array($to, '');
		}
		$this->_params['to'] = $to;
		return $this;
	}
	
	
	/**
	 * Get to address
	 * @return Array
	 */
	public function getTo() {
		return !empty($this->_params['to']) ? $this->_params['to'] : array();
	}
	
	
	/**
	 * Add skippable key
	 * @param String $key
	 * @return $this
	 */
	public function addSkippableKey($key) {
		$this->_skippableKeys[] = $key;
	}
	
	
	/**
	 * Set an alias for a certain posted key
	 * @param String $key
	 * @param String $alias
	 * @return $this
	 */
	public function setAlias($key, $alias) {
		$this->_aliases[$key] = $alias;
		return $this;
	}
	
	
	/**
	 * Get an alias for a certain posted key
	 * @param String $key
	 * @return String Alias
	 */
	public function getAlias($key) {
		return !empty($this->_aliases[$key]) ? $this->_aliases[$key] : ucfirst($key);
	}
	
	
	/**
	 * Set error 
	 * @param String $error
	 * @return $this
	 */
	public function addError($error) {
		$this->_errors[] = $error;
		return $this;
	}
	
	
	/**
	 * Get errors
	 * @return Array 
	 */
	public function getErrors() {
		return $this->_errors;
	}
	
	
	/**
	 * Check if the submitted data is valid
	 * @param Array $requiredFields 
	 * @return Boolean description
	 */
	public function isValid(array $requiredFields = array()) {
		// check if all values required to send the mail are set
		if (empty($this->_params['body']) ||
			empty($this->_params['from']) ||
			empty($this->_params['to']) ||
			empty($this->_params['subject'])) {
			$this->addError('Not all required mail parameters were given.');
			return false;
		}
		
		// check that at least 1 second passed from form rendering to form submit
		if (array_key_exists(self::TIMESTAMP_KEY, $this->_postParams) &&
			time()-$this->_postParams[self::TIMESTAMP_KEY] <= 1) {
			$this->addError('Timestamp difference is less than or equal to 1 second.');
			return false;
		}
		
		// check if the honeypot was filled
		if (array_key_exists(self::HONEYPOT_KEY, $this->_postParams) &&
			!empty($this->_postParams[self::HONEYPOT_KEY])) {
			$this->addError('Honeypot was filled.');
			return false;
		}
		
		// check if all required fields were filled
		foreach ($requiredFields as $field) {
			if (empty($this->_postParams[$field])) {
				$this->addError('Required field '.$field.' is empty.');
				return false;
			}
		}
		return true;
	}
	
	
	/**
	 * Create a list of values from posted variables
	 * @param Array $postParams
	 * @return String
	 */
	public function composeListFromPost(array $postParams) {
		$out  = 'De volgende waardes zijn ingevuld:';
		$out .= self::NEWLINE.self::NEWLINE;
		foreach ($postParams as $key => $value) {
			if (in_array($key, $this->_skippableKeys)) {
				continue;
			}
			if (is_numeric($value) && ($value == 1 || $value == 0)) {
				/**
				 * @todo Do we have to internationalize this?
				 */
				$value = (int)$value ? 'ja' : 'nee';
			}
			$out .= '- '.$this->getAlias($key).': '.$value.self::NEWLINE;
		}
		$out .= self::NEWLINE;
		return $out;
	}
	
	
	/**
	 * Send the mail.
	 * @return Boolean
	 */
	public function send() {
		if ($this->isValid()) {
			$postList = $this->composeListFromPost($this->_postParams);			
			$this->_mail->setBodyText($this->_params['body'].$postList);
			$this->_mail->setFrom($this->_params['from'][0], $this->_params['from'][1]);
			$this->_mail->addTo($this->_params['to'][0], $this->_params['to'][1]);
			$this->_mail->setSubject($this->_params['subject']);
			return $this->_mail->send();
		}
		return false;
	}
}