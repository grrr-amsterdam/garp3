<?php
/**
 * Garp_Config_Ini_Expander
 * Allows for the key "config" in a config object to contain paths to ini files that 
 * are to be included in the same way as Zend_Application does.
 *
 * The necessary methods are taken from Zend_Application. If only they'd
 * made it a public API...
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Garp_Config_Ini
 */
class Garp_Config_Ini_Expander {
	/**
	 * Root file
	 * @var Zend_Config
	 */
	protected $_root;

	/**
	 * Class constructor
	 * @param Zend_Config $root
	 * @return Void
	 */
	public function __construct(Zend_Config $root) {
		$this->_root = $root;
	}

	/**
	 * Returned the expanded config object
	 * @return Zend_Config
	 */
	public function getExpanded() {
		$options = $this->_root->toArray();
		if (empty($options['config'])) {
			return $this->_root;
		}

		if (!is_array($options['config'])) {
			$options['config'] = (array)$options['config'];
		}
		$_options = array();
		foreach ($options['config'] as $tmp) {
			$_options = $this->mergeOptions($_options, $this->_loadConfig($tmp));
		}
		$options = $this->mergeOptions($_options, $options);
		return new Zend_Config($options);
	}

	/**
	 * Merge options recursively
	 *
	 * @param  array $array1
	 * @param  mixed $array2
	 * @return array
	 */
	public function mergeOptions(array $array1, $array2 = null) {
		if (!is_array($array2)) {
			return $array1;
		}
		array_walk($array2, array($this, '_mergeOption'), $array1);
		foreach ($array2 as $key => $val) {
			$this->_mergeOption($val, $key, $array1);
		}
		return $array1;
	}

	/**
	 * Merge an option into another array.
	 * @param Mixed $option
	 * @return Void
	 */
	protected function _mergeOption($val, $key, &$array1) {
		if (is_array($val)) {
			$val = (array_key_exists($key, $array1) && is_array($array1[$key]))
				? $this->mergeOptions($array1[$key], $val)
				: $val
			;
		}
		$array1[$key] = $val;
	}

	/**
	 * Load config file
	 * @return Zend_Config
	 */
	protected function _loadConfig($file) {
		$environment = $this->_root->getSectionName();
		$suffix      = pathinfo($file, PATHINFO_EXTENSION);
		$suffix      = ($suffix === 'dist')
					 ? pathinfo(basename($file, ".$suffix"), PATHINFO_EXTENSION)
					 : $suffix;

		switch (strtolower($suffix)) {
			case 'ini':
				$config = new Zend_Config_Ini($file, $environment);
			break;

			case 'xml':
				$config = new Zend_Config_Xml($file, $environment);
			break;

			case 'json':
				$config = new Zend_Config_Json($file, $environment);
			break;

			case 'yaml':
			case 'yml':
				$config = new Zend_Config_Yaml($file, $environment);
			break;

			case 'php':
			case 'inc':
				$config = include $file;
				if (!is_array($config)) {
					throw new Zend_Application_Exception('Invalid configuration file provided; PHP file does not return array value');
				}
				return $config;
			break;

			default:
				throw new Zend_Application_Exception('Invalid configuration file provided; unknown config type');
		}

		return $config->toArray();
	}
}
