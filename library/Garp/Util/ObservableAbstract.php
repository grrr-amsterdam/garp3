<?php
/**
 * Garp_Util_ObservableAbstract
 * Implementation of the Observable design pattern
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Util
 * @lastmodified $Date: $
 */
abstract class Garp_Util_ObservableAbstract implements Garp_Util_Observable {
	/**
	 * Collection of observers
	 * @var Array
	 */
	protected $_observers = array();
	
	
	/**
	 * Register observer. The observer will then listen to events broadcasted
	 * from this class.
	 * @param Garp_Util_Observer $observer The observer
	 * @param String $name Optional custom name
	 * @return Garp_Util_Observable $this
	 */
	public function registerObserver(Garp_Util_Observer $observer, $name = false) {
		$this->_observers[$observer->getName()] = $observer;
		return $this;
	}
	
	
	/**
	 * Unregister observer. The observer will no longer listen to 
	 * events broadcasted from this class.
	 * @param Garp_Util_Observer|String $name The observer or its name
	 * @return Garp_Util_Observable $this
	 */
	public function unregisterObserver($name) {
		if (!is_string($name)) {
			$observer = $observer->getName();
		}
		unset($this->_observers[$name]);
		return $this;
	}
	
	
	/**
	 * Broadcast an event. Observers may implement their reaction however
	 * they please. The Observable does not expect a return action.
	 * If Observers are allowed to modify variables passed, make sure
	 * $args contains references instead of values.
	 * @param String $event The event name
	 * @param Array $args The arguments you wish to pass to the observers
	 * @return Garp_Util_Observable $this
	 */
	public function notifyObservers($event, array $args = array()) {
		foreach ($this->_observers as $observer) {
			$observer->receiveNotification($event, $args);
		}
		return $this;
	}
}