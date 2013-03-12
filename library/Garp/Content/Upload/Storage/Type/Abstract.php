<?php
/**
 * Garp_Content_Upload_Storage_Type_Abstract
 * You can use an instance of this class as a numeric array, containing an array per entry:
 * 		array(
 *			'uploads/images/pussy.gif',
 *			'uploads/images/cat.jpg'
 *		)
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
abstract class Garp_Content_Upload_Storage_Type_Abstract implements Garp_Content_Upload_Storage_Protocol {
	const ERROR_CANT_OPEN_DIRECTORY = "Unable to open the configuration directory: %s";

	protected $_uploadTypes = array('document', 'image');

	protected $_environment;
	
	/**
	 * Configuration as defined in application.ini under the given environment.
	 */
	protected $_ini;
	


	/**
	 */
	public function __construct($environment) {
		$this->_setEnvironment($environment);
		$this->_setIni();
	}
	
	/**
	 * @return Array
	 */
	public function getUploadTypes() {
		return $this->_uploadTypes;
	}
	
	
	public function getEnvironment() {
		return $this->_environment;
	}


	protected function _setEnvironment($environment) {
		$this->_environment = $environment;
	}
	
	
	protected function _setIni() {
		$this->_ini = new Zend_Config_Ini(APPLICATION_PATH.'/configs/application.ini', $this->_environment);
	}


	/**
	 * Returns the relative path to this filetype
	 * @param 	String $type 	Filetype, should be one of $this->_uploadTypes
	 * @return 	String 			Relative path to this type of file
	 */
	protected function _getRelPath($filename, $type) {
		$ini = $this->_getIni();
		return $ini->cdn->path->upload->{$type} . DIRECTORY_SEPARATOR . $filename;
	}

	protected function _getIni() {
		return $this->_ini;
	}

	/**
	 * @return Array	An array containing the upload types (i.e. 'document' or 'image') as key,
	 *					and the relative directory path of this type as value.
	 */
	protected function _getConfiguredPaths() {
		$paths = array();
		$ini = $this->_getIni();
		
		foreach ($this->_uploadTypes as $uploadType) {
			$paths[$uploadType] = $ini->cdn->path->upload->{$uploadType};
		}
		
		return $paths;
	}


	protected function _throwDirAccessError($dir) {
		$errorMsg = sprintf(self::ERROR_CANT_OPEN_DIRECTORY, $dir);
		throw new Exception($errorMsg);
	}
}