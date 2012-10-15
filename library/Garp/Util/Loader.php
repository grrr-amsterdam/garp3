<?php
require_once 'Garp/Util/Configuration.php';

/**
 * Garp_Util_Loader
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
 	 * Singleton
 	 * @var Garp_Util_Loader
 	 */
	protected static $_instance;


	/**
	 * The character you use to mimic a namespaced system in your class names (usually "_").
	 * @var String
	 */
	protected $_folderSeparator = '_';
	

	/**
	 * The search roots. Multiple roots possible, specified on namespace.
	 * @var String
	 */
	protected $_includePaths = array();
	
	
	/**
	 * Collection of string manipulation functions
	 * @var Array
	 */
	protected $_filters = array();
	

	/**
 	 * Check wether the file exists before including it.
 	 * Set to true when you want this loader to be chainable. It will return 
 	 * false if the file does not exist, allowing other autoloaders to be 
 	 * registered and try their luck.
 	 * @var Boolean
 	 */
	protected $_checkIfFileExists = false;

	
	/**
	 * Class constructor
	 * @param Array|Garp_Util_Configuration $options
	 * @return Void
	 */
	private function __construct($options = array()) {
		if ($options) {
			$options = $options instanceof Garp_Util_Configuration ? $options : new Garp_Util_Configuration($options);
			$options->obligate('paths')
					->setDefault('autoRegister', false);

			foreach ($options['paths'] as $path) {
				$this->addIncludePath($path);
			}

			if ($options['autoRegister']) {
				$this->register();
			}
		}
	}


	/**
 	 * Singleton interface
 	 * @param Array|Garp_Util_Configuration $options
 	 * @return Void
 	 */
	public static function getInstance($options = array()) {
		if (!self::$_instance) {
			self::$_instance = new Garp_Util_Loader($options);
		}
		return self::$_instance;
	}


	/**
 	 * Do a file_exists check before including the file.
 	 * @param Boolean $flag
 	 * @return $this
 	 */
	public function checkIfFileExists($flag) {
		$this->_checkIfFileExists = $flag;
		return $this;
	}
	
	
	/**
	 * Set the include path (meaning, in this case, the root from which class files are found).
	 * $options may contain three config values: 'path', the actual path, 'namespace', to use the path exclusively for
	 * certain namespaces, and 'ignore'. This last one is used to specify strings at the start of the classname that
	 * are not used in generating the path to the class file.
	 * Example: if 'ignore' is "Foo_Bar" and include path = "/my/classes/", the class "Foo_Bar_Baz" will be searched for 
	 * in "/my/classes/Baz.php". If 'ignore' would've been NULL, the generated path would be "/my/classes/Foo/Bar/Baz.php". 
	 * @param Array|Garp_Util_Configuration $options
	 * @return $this
	 */
	public function addIncludePath($options) {
		$options = $options instanceof Garp_Util_Configuration ? $options : new Garp_Util_Configuration($options);
		$options->obligate('path')
				->setDefault('namespace', '*')
				->setDefault('ignore', null)
				->setDefault('extension', 'php');

		$options['path'] = rtrim($options['path'], DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
		$options['extension'] = ltrim($options['extension'], '.');
		$this->_includePaths[$options['namespace']] = array(
			'path' => $options['path'],
			'ignore' => $options['ignore'],
			'extension' => $options['extension'],
		);
		return $this;
	}
	
	
	/**
	 * Get one of the registered include paths
	 * @param String $namespace Return path for given namespace only
	 * @return String
	 */
	public function getIncludePath($namespace = '*') {
		if (!array_key_exists($namespace, $this->_includePaths)) {
			throw new Garp_Util_Loader_Exception("Namespace $namespace not registered");
		}
		return $this->_includePaths[$namespace];
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
		return $this;
	}
	
	
	/**
	 * Load a class file
	 * @param String $className The name of the class to load
	 * @return Bool Wether the class could be loaded.
	 */
	public function loadClass($className) {
		$path = $this->getPath($className);
		if ($this->_checkIfFileExists) {
			if (!file_exists($path)) {
				return false;
			}
		}
		return include_once($path);
	}


	/**
 	 * Check if a class is loadable
 	 * @param String $className The name of the class to load
 	 * @return Bool
 	 */
	public function isLoadable($className) {
		$path = $this->getPath($className);
		return is_readable($path);
	}


	/**
 	 * Construct the path to a class file
 	 * @param String $className
 	 * @return String
 	 */
	public function getPath($className) {
		$className = $this->_getFilteredClassName($className);
		$separator = $this->_folderSeparator;
		if (strpos($className, self::NAMESPACE_SEPARATOR) !== false) {
			$separator = self::NAMESPACE_SEPARATOR;
		}
		$includePath = null;
		$extension = 'php';
		$ignore = null;

		// Use the asterisk path as default...
		if (!empty($this->_includePaths['*'])) {
			$includePath = $this->_includePaths['*']['path'];
			$extension = $this->_includePaths['*']['extension'];
			$ignore = $this->_includePaths['*']['ignore'];
		}
		// ...but try to find a better fit based on namespace
		foreach ($this->_includePaths as $namespace => $path) {
			if ($namespace == '*') {
				continue;
			}
			// See if the class belongs to the registered namespace
			if (substr($className, 0, strlen($namespace)) == $namespace) {
				$includePath = $path['path'];
				$extension = $path['extension'];
				$ignore = $path['ignore'];
				break;
			}
		}

		if (is_null($includePath)) {
			throw new Garp_Util_Loader_Exception("Could not resolve path for class $className.");
		}

		// cut off an optional prefix
		if ($ignore) {
			$className = preg_replace('/^'.preg_quote($ignore).'/', '', $className);
		}
		$includePath .= str_replace($separator, DIRECTORY_SEPARATOR, $className);
		$includePath .= '.'.$extension;
		return $includePath;
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
