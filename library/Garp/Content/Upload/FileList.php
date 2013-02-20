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
class Garp_Content_Upload_FileList extends ArrayObject {
	const HIDDEN_FILES_PREFIX = '.';

	const STORAGE_TYPE_WEBSERVER_LOCAL = 'storage type: webserver local';
	const STORAGE_TYPE_WEBSERVER_REMOTE = 'storage type: webserver remote';
	const STORAGE_TYPE_S3 = 'storage type: s3';

	protected $_uploadTypes = array('documents', 'images');
	
	protected $_environment;
	
	protected $_storageType;


	/**
	 */
	public function __construct($environment) {
		$this->_setEnvironment($environment);
		$this->_setStorageType($this->_getStorageType());
		
		$this->_buildList();
	}
	
	
	protected function _buildList() {
	}
	
	
	protected function _setEnvironment($environment) {
		$this->_environment = $environment;
	}
	
	
	protected function _setStorageType($storageType) {
		$this->_storageType = $storageType;
	}
	
	
	protected function _getStorageType() {
		$ini = new Zend_Config_Ini(APPLICATION_PATH.'/configs/application.ini', $this->_environment);
		$cdnType = $ini->cdn->type;
		
		switch($cdnType) {
			case 'local':
				if ($this->_environment === 'development') {
					return self::STORAGE_TYPE_WEBSERVER_LOCAL;
				} else return self::STORAGE_TYPE_WEBSERVER_REMOTE;
			break;
			case 's3':
				return self::STORAGE_TYPE_S3;
			break;
			default:
				throw new Exception('Unknown CDN type.');
		}
	}


	// protected function _addValidAssetFile($fileName, $filePathAbs) {
	// 	$filePathRel = substr($filePathAbs, $this->_baseDirLength);
	// 
	// 	if (
	// 		(!$this->_filterString || stripos($filePathRel, $this->_filterString) !== false) &&
	// 		$this->_isWithinTimeFrame($filePathAbs)
	// 	) {
	// 		$this[] = $filePathRel;
	// 	}
	// }
	
	
	// protected function _isValidAssetName($nodeName) {
	// 	return !($nodeName[0] === self::HIDDEN_FILES_PREFIX);
	// }
}