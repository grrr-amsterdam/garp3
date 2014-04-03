<?php
/**
 * Garp_Controller_Helper_FlashMessenger
 * Based on Zend_Controller_Helper_FlashMessenger, but is configurable to use cookies instead of sessions.
 * NOTE: this variant is by no means as extensive as the Zend one, but who needs that anyway.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Controller
 * @lastmodified $Date: $
 */
class Garp_Controller_Helper_FlashMessenger extends Zend_Controller_Action_Helper_Abstract {
	/**
 	 * Session or cookie
 	 * @var Garp_Store_Interface
 	 */
	protected static $_store;


	/**
 	 * Class constructor
 	 * @return Void 
 	 */
	public function __construct() {
		self::$_store = Garp_Store_Factory::getStore('FlashMessenger');
	}


	public function postDispatch() {
		if (self::$_store instanceof Garp_Store_Cookie && self::$_store->isModified()) {
			self::$_store->writeCookie();
		}
	}


	/**
 	 * Add message
 	 * @param String $message
 	 * @return $this
 	 */
	public function addMessage($message) {
		if (!is_array(self::$_store->messages)) {
			self::$_store->messages = array();
		}
		$messages = self::$_store->messages;
		$messages[] = $message;
		self::$_store->messages = $messages;
		return $this;
	}


	/**
 	 * Get messages
 	 * @param Boolean $preserveMessages Wether to keep the messages. 
 	 * Of course this is not the general idea of the FlashMessenger. Usually messages are directly discardable.
 	 * @return Array
 	 */
	public function getMessages($preserveMessages = false) {
		if ($this->hasMessages()) {
			$messages = self::$_store->messages;
			if (!$preserveMessages) {
				$this->clearMessages();
			}
			return $messages;
		}
		return array();
	}


	/**
 	 * Check if messages are set
 	 * @return Boolean
 	 */
	public function hasMessages() {
		return is_array(self::$_store->messages);
	}


	/**
 	 * Remove messages
 	 * @return $this
 	 */
	public function clearMessages() {
		self::$_store->destroy();
	}


	/**
   * Strategy pattern: proxy to addMessage()
   *
   * @param  string $message
   * @return void
   */
  public function direct($message) {
    return $this->addMessage($message);
  }
}
