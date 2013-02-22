<?php
/**
 * Garp_Content_Upload_FileList
 * You can use an instance of this class as a numeric array, containing an array per entry:
 * 		array(
 *			'path' => 'uploads/images/pussy.gif',
 *			'lastmodified' => '1361378985'
 *		)
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
abstract class Garp_Content_Upload_Storage_Type_Abstract implements Garp_Content_Upload_Storage_Behavior_Listable {
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
	
	
	public function getEnvironment() {
		return $this->_environment;
	}


	protected function _setEnvironment($environment) {
		$this->_environment = $environment;
	}
	
	
	protected function _setIni() {
		$this->_ini = new Zend_Config_Ini(APPLICATION_PATH.'/configs/application.ini', $this->_environment);
	}


	protected function _getConfiguredPaths() {
		$paths = array();
		
		foreach ($this->_uploadTypes as $uploadType) {
			$paths[] = $this->_ini->cdn->path->upload->{$uploadType};
		}
		
		return $paths;
	}


	protected function _throwDirAccessError($dir) {
		$errorMsg = sprintf(self::ERROR_CANT_OPEN_DIRECTORY, $dir);
		throw new Exception($errorMsg);
	}
}