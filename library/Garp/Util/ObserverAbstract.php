<?php
/**
 * Garp_Util_ObserverAbstract
 * Blueprint for observers, following the Observer design pattern.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
abstract class Garp_Util_ObserverAbstract implements Garp_Util_Observer {
	/**
	 * It's handy for Observers to have a unique 
	 * identifier. Return that here. 
	 * By default the base classname will be returned (e.g. "Garp_Util_Observer" becomes "Observer")
	 * @return String
	 */
	public function getName() {
		$className = get_class($this);
		$classParts = explode('_', $className);
		return array_pop($classParts);
	}
	
	
	/**
	 * Receive events. This method looks for a method named after 
	 * the event (e.g. when the event is "beforeFetch", the method 
	 * executed will be "beforeFetch"). Subclasses may implement
	 * this to act upon the event however they wish.
	 * @param String $event The name of the event
	 * @param Array $params Collection of parameters (contextual to the event)
	 * @return Void
	 */
	public function receiveNotification($event, array $params = array()) {
		if (method_exists($this, $event)) {
			$this->{$event}($params);
		}
	}
}