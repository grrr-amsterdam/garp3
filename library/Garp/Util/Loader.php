<?php
/**
 * Garp_Util_Loader
 * Class autoloader. Can work with namespaces as well as with the underscore strategy (e.g. "Garp_Util_Loader_Abstract" maps to folder "Garp/Util/Loader/Abstract.php")
 * 
 * @author Harmen Janssen | grrr.nl
 * @version 1.0
 * @package Garp
 * @subpackage Util
 */
class Garp_Util_Loader {
	/**
	 * Namespace separator char
	 * @var String
	 */
	const NAMESPACE_SEPARATOR = '\\';


	/**
	 * The character you use to mimic a namespaced system in your class names (usually "_").
	 * @var String
	 */
	protected $_folderSeparator = '_';
	

	/**
	 * The search root
	 * @var String
	 */
	protected $_includePath;
	
	
	/**
	 * File extension
	 * @var String
	 */
	protected $_fileExtension = '.php';
	
	
	/**
	 * Prefix string
	 * @var
	 */
	protected $_prefix = '';
	
	
	/**
	 * Collection of string manipulation functions
	 * @var Array
	 */
	protected $_filters = array();
	
	
	/**
	 * Class constructor
	 * @param String $includePath The library root
	 * @param Bool $autoRegister Wether this loader should register automatically to the SPL autoload stack
	 * @return Void
	 */
	public function __construct($includePath, $autoRegister = false) {
		$this->setIncludePath($includePath);
		if ($autoRegister) {
			$this->register();
		}
	}
	
	
	/**
	 * Set the include path (meaning, in this case, the root from which class files are found).
	 * @param String $includePath The path
	 * @return $this
	 */
	public function setIncludePath($includePath) {
		if (substr($includePath, -1) !== DIRECTORY_SEPARATOR) {
			$includePath .= DIRECTORY_SEPARATOR;
		}
		$this->_includePath = $includePath;
		return $this;
	}
	
	
	/**
	 * Get the current include path
	 * @return String
	 */
	public function getIncludePath() {
		return $this->_includePath;
	}
	
	
	/**
	 * Set folder separator
	 * @param String $char The character separating "namespaces"
	 * @return $this
	 */
	public function setFolderSeparator($char) {
		$this->_folderSeparator = $char;
		return $this;
	}
	
	
	/**
	 * Get folder separator
	 * @return String
	 */
	public function getFolderSeparator() {
		return $this->_folderSeparator;
	}
	
	
	/**
	 * Set file extension
	 * @param String $ext
	 * @return $this
	 */
	public function setFileExtension($ext) {
		$this->_fileExtension = $ext;
		return $this;
	}
	
	
	/**
	 * Get folder separator
	 * @return String
	 */
	public function getFileExtension() {
		return $this->_fileExtension;
	}
	
	
	/**
	 * Set prefix string. This means the following;
	 * if prefix is 'Foo_Bar', and the requested className = 'Foo_Bar_Service_Unit' the 
	 * class file will be "<includePath>/Service/Unit.<fileExtension>".
	 * In other words; a prefix string is a substring at the beginning of the className that's ignored
	 * in the path finding.
	 * This setup is used inside /application to load model files.
	 * @param String $prefix
	 * @return $this
	 */
	public function setPrefix($prefix) {
		$this->_prefix = $prefix;
		return $this;
	}
	
	
	/**
	 * Get prefix string.
	 * @return String
	 */
	public function getPrefix() {
		return $this->_prefix;
	}
	
	
	/**
	 * Add a filter to the stack.
	 * Note that the filter will be tested for validity: it MUST return 
	 * a string.
	 * @param String $alias String alias, so you can retrieve the filter
	 * @param Closure $filter
	 * @return $this
	 */
	public function addFilter($alias, Closure $filter) {
		$test = 'Garp_Foo_Bar';
		if (!is_string($filter($test))) {
			throw new Garp_Util_Loader_Exception('Filter "'.$alias.'" is invalid. Returntype MUST be \'string\'.');
		}		
		
		$this->_filters[$alias] = $filter;
		return $this;
	}
	
	
	/**
	 * Retrieve a filter
	 * @param String $alias
	 * @return Closure
	 */
	public function getFilter($alias) {
		return array_key_exists($alias, $this->_filters) ? $this->_filters[$alias] : null;
	}
	
	
	/**
	 * Remove filter
	 * @param String $alias
	 * @return $this
	 */
	public function removeFilter($alias) {
		unset($this->_filters[$alias]);
		return $this;
	}
	
	
	/**
	 * Installs this class loader on the SPL autoload stack.
	 * @return $this
	 */
	public function register() {
		spl_autoload_register(array($this, 'loadClass'));
		return $this;
	}
	
	
	/**
	 * Uninstalls this class loader from the SPL autoload stack.
	 * @return $this
	 */
	public function unregister() {
		spl_autoload_unregister(array($this, 'loadClass'));
	}
	
	
	/**
	 * Load a class file
	 * @param String $className The name of the class to load
	 * @return Bool Wether the class could be loaded.
	 */
	public function loadClass($className) {
		$className = $this->_getFilteredClassName($className);
		
		$separator = $this->_folderSeparator;
		if (strpos($className, self::NAMESPACE_SEPARATOR) !== false) {
			$separator = self::NAMESPACE_SEPARATOR;
		}
		// cut of the prefix
		$className = preg_replace('/^'.preg_quote($this->_prefix).'/', '', $className);
		$path = $this->_includePath;
		$path .= str_replace($separator, DIRECTORY_SEPARATOR, $className);
		$path .= $this->_fileExtension;
		// @todo Is this file_exists call unnecessarily heavy?
		if (file_exists($path)) {
			require_once $path;
			return true;
		}
		return false;
	}
	
	
	/**
	 * Apply registered filters to the classname
	 * @param String $className
	 * @return String
	 */
	protected function _getFilteredClassName($className) {
		foreach ($this->_filters as $filter) {
			$className = $filter($className);
		}
		return $className;
	}
}