<?php
/**
 * Garp_Content_Upload_FileList_Abstract
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
abstract class Garp_Content_Upload_FileList_Abstract extends ArrayObject {
	const HIDDEN_FILES_PREFIX = '.';

	const ERROR_CANT_OPEN_DIRECTORY = "Unable to open the configuration directory: %s";

	protected $_uploadTypes = array('document', 'image');

	protected $_bannedBaseNames = array('scaled');
	
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
		
		$this->_buildList();
	}


	/**
	 * @param $path			The relative path plus filename. F.i. '/uploads/images/pussy.gif'
	 * @param $lastmodified	Timestamp of file's last modification date.
	 */
	public function addEntry($path, $lastmodified) {
		if ($this->_isValidAssetName($path)) {
			$this[] = array(
				'path' => $path,
				'lastmodified' => $lastmodified
			);
		}
	}

	
	protected function _setEnvironment($environment) {
		$this->_environment = $environment;
	}
	
	
	protected function _setIni() {
		$this->_ini = new Zend_Config_Ini(APPLICATION_PATH.'/configs/application.ini', $this->_environment);
	}


	protected function _listConfiguredPaths() {
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
	
	
	protected function _isValidAssetName($path) {
		$baseName = basename($path);
		return (
			!($baseName[0] === self::HIDDEN_FILES_PREFIX) &&
			!in_array($baseName, $this->_bannedBaseNames)
		);
	}
}