<?php
/**
 * @author David Spreekmeester | Grrr.nl
 */
class Garp_Model_Spawn_Config_Storage_File implements Garp_Model_Spawn_Config_Storage_Interface {
	protected $_directory;
	protected $_extension;
	protected $_ignoreFiles = array('.', '..', '.svn');


	public function __construct($directory, $extension) {
		$this->_directory = $this->_addTrailingSlash($directory);
		$this->_extension = $extension;
	}


	public function load($objectId) {
		$path = $this->_getPath($objectId);
		$this->_validatePath($path);

		$contents = file_get_contents($path);

		if (!strlen($contents)) {
			throw new Exception("The configuration file is empty. Fill it at ".$path);
		}

		return $contents;
	}


	public function listObjectIds() {
		$filenames = array();
		$suffixLength = strlen($this->_extension) + 1;

		if ($handle = opendir($this->_directory)) {
			while (false !== ($filename = readdir($handle))) {
				if (!in_array($filename, $this->_ignoreFiles)) {
					$filenames[] = substr($filename, 0, -$suffixLength);
				}
			}
		} else throw new Exception('Unable to open the configuration directory at '.$this->_directory);

		return $filenames;
	}
	
	
	protected function _addTrailingSlash($directory) {
		return $directory . ($directory[strlen($directory) - 1] === '/' ? '' : '/');
	}
	
	
	protected function _getPath($objectId) {
		return $this->_directory . $objectId . '.' . $this->_extension;
	}


	protected function _validatePath($path) {
		if (!is_file($path)) {
			throw new Exception("The provided path ({$path}) is not a file.");
		} elseif (!file_exists($path)) {
			throw new Exception("The configuration file does not exist yet. Create one at ".$path);
		} elseif (
			($suffixLength = strlen($this->_extension) + 1) &&
			substr(basename($path), -$suffixLength, $suffixLength) !== ('.' . $this->_extension)
		) {
			throw new Exception("The configuration file does not have the proper extension. It should end in '.{$this->_extension}'.");
		}
	}
}