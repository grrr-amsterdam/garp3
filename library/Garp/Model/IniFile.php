<?php
/**
 * Garp_Model_IniFile
 * Models that do not interact with database tables, but with .ini files.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class Garp_Model_IniFile implements Garp_Model, Garp_Util_Observer, Garp_Util_Observable {
	/**
	 * Which backend ini file to use
	 * @var String
	 */
	protected $_file;
	
	
	/**
	 * Which namespace to use
	 * @var String
	 */
	protected $_namespace;
	
	
	/**
	 * The ini backend
	 * @var Zend_Config_Ini
	 */
	protected $_ini;
	
	
	/**
	 * Class constructor
	 * @param String $file The path to the ini file
	 * @param String $namespace What namespace to use in the ini file
	 * @return Void
	 */
	public function __construct($file = null, $namespace = null) {
		$file = $file ?: APPLICATION_PATH.'/configs/'.$this->_file;
		$namespace = $namespace ?: $this->_namespace;
		if ($file) {
			$this->init($file, $namespace);
		}
	}
	
	
	/**
	 * Initialize the ini file.
	 * @param String $file The path to the ini file
	 * @param String $namespace What namespace to use in the ini file
	 * @return Void
	 */
	public function init($file, $namespace = null) {
		$ini = Garp_Cache_Ini::factory($file);
		if ($namespace) {
			$namespaces = explode('.', $namespace);
			do {
				$namespace = array_shift($namespaces);
				$ini = $ini->{$namespace};
			} while ($namespaces);
		}
		$this->_ini = $ini;
	}
	
	
	/**
	 * Fetch all entries
	 * @return Array
	 */
	public function fetchAll() {
		return $this->_ini->toArray();
	}
	
	
	/**
	 * Count all entries
	 * @return Int
	 */
	public function count() {
		return count($this->_ini->toArray());
	}
	
	
	/**
	 * Observable methods
	 * ----------------------------------------------------------------------
	 */
	
	/**
	 * Register observer. The observer will then listen to events broadcasted
	 * from this class.
	 * @param Garp_Util_Observer $observer The observer
	 * @param String $name Optional custom name
	 * @return Garp_Util_Observable $this
	 */
	public function registerObserver(Garp_Util_Observer $observer, $name = false) {
		$name = $name ?: $observer->getName();
		$this->_observers[$name] = $observer;
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
		$first = $middle = $last = array();
		
		// Distribute observers to the different arrays
		foreach ($this->_observers as $observer) {
			// Core helpers may define when they are executed; first or last.
			if ($observer instanceof Garp_Model_Helper_Core) {
				if (Garp_Model_Helper_Core::EXECUTE_FIRST === $observer->getExecutionPosition()) {
					$first[] = $observer;
				} elseif (Garp_Model_Helper_Core::EXECUTE_LAST === $observer->getExecutionPosition()) {
					$last[] = $observer;
				}
			} else {
				// Regular observers are always executed in the middle
				$middle[] = $observer;
			}
		}
		
		// Do the actual execution
		foreach (array($first, $middle, $last) as $observerCollection) {
			foreach ($observerCollection as $observer) {
				$observer->receiveNotification($event, $args);
			}
		}
		return $this;
	}
	
	
	/**
	 * Observer methods
	 * ----------------------------------------------------------------------
	 */
	
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
	
	
	/**
	 * Return table name
	 * @return String
	 */
	public function getName() {
		return $this->_name;
	}
}
