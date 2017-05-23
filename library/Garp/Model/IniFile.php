<?php
/**
 * Garp_Model_IniFile
 * Models that do not interact with database tables, but with .ini files.
 *
 * @package Garp_Model
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Model_IniFile implements Garp_Model, Garp_Util_Observer, Garp_Util_Observable {
    /**
     * Which backend ini file to use
     *
     * @var string
     */
    protected $_file;

    /**
     * Which namespace to use
     *
     * @var string
     */
    protected $_namespace;

    /**
     * The ini backend
     *
     * @var Zend_Config_Ini
     */
    protected $_ini;

    /**
     * Wether we're currently in CMS context.
     *
     * @var bool
     */
    protected $_cmsContext = false;

    /**
     * Class constructor
     *
     * @param string $file The path to the ini file
     * @param string $namespace What namespace to use in the ini file
     * @return void
     */
    public function __construct($file = null, $namespace = null) {
        $file = $file ?: APPLICATION_PATH . '/configs/' . $this->_file;
        $namespace = $namespace ?: $this->_namespace;
        if ($file) {
            $this->init($file, $namespace);
        }
    }

    /**
     * Initialize the ini file.
     *
     * @param string $file The path to the ini file
     * @param string $namespace What namespace to use in the ini file
     * @return void
     */
    public function init($file, $namespace = null) {
        $ini = Garp_Config_Ini::getCached($file);
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
     *
     * @return array
     */
    public function fetchAll() {
        return $this->_ini->toArray();
    }

    /**
     * Count all entries
     *
     * @return int
     */
    public function count() {
        return count($this->_ini->toArray());
    }

    /**
     * Set wether we are in CMS context.
     * This replaces the need for the global Zend_Registry::get('CMS') that's used in the past.
     * The difference between the two methods is with this new method the context is set per
     * instance as opposed to globally for every `new Model` anywhere in the current process.
     *
     * @param bool $isCmsContext
     * @return $this
     */
    public function setCmsContext($isCmsContext) {
        $this->_cmsContext = $isCmsContext;
        return $this;
    }

    /**
     * Grab wether we're in cms context.
     *
     * @return bool
     */
    public function isCmsContext() {
        return $this->_cmsContext;
    }

    /**
     * Observable methods
     * ----------------------------------------------------------------------
     */

    /**
     * Register observer. The observer will then listen to events broadcasted
     * from this class.
     *
     * @param Garp_Util_Observer $observer The observer
     * @param string $name Optional custom name
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
     *
     * @param Garp_Util_Observer|string $name The observer or its name
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
     *
     * @param string $event The event name
     * @param array $args The arguments you wish to pass to the observers
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
                } elseif (Garp_Model_Helper_Core::EXECUTE_LAST === $observer->getExecutionPosition()
                ) {
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
     *
     * @param string $event The name of the event
     * @param array $params Collection of parameters (contextual to the event)
     * @return void
     */
    public function receiveNotification($event, array $params = array()) {
        if (method_exists($this, $event)) {
            $this->{$event}($params);
        }
    }

    /**
     * Return table name
     *
     * @return string
     */
    public function getName() {
        return $this->_name;
    }
}
