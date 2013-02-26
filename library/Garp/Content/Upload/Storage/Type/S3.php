<?php
/**
 * Garp_Content_Upload_Storage_Type_S3
 * 
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
class Garp_Content_Upload_Storage_Type_S3 extends Garp_Content_Upload_Storage_Type_Abstract {
	protected $_service;


	public function __construct($environment) {
		parent::__construct($environment);
		$this->_setService();
	}


	public function fetchFileList() {
		$fileList = new Garp_Content_Upload_FileList();

		$service = $this->_getService();

		$uploadTypePaths = $this->_getConfiguredPaths();
		
		foreach ($uploadTypePaths as $dirPath) {
			$service->setPath($dirPath);
			$dirList = $service->getList();
			
			foreach ($dirList as $filePath) {
				if ($filePath[strlen($filePath) - 1] !== '/') {
					$fileList->addEntry('/' . $filePath);
				}
			}
		}

		return $fileList;
	}


	/**
	 * Calculate the eTag of a file.
	 * @param String $path 	Relative path to the file, starting with a slash.
	 * @return String 		Content hash (md5 sum of the content)
	 */
	public function fetchEtag($path) {
		$service = $this->_getService();
		$filename = basename($path);
		$dir = substr($path, 0, strlen($path) - strlen($filename));
		$service->setPath($dir);

		return $service->getEtag($filename);
	}

	
	/**
	 * Find the last modification date of the provided file.
	 * @param 	String 	$path 	Relative path to the file
	 * @return 	Int 			Unix timestamp of the last modification date.
	 */
	public function findLastModified($path) {
		$service = $this->_getService();
		$filename = basename($path);
		$dir = substr($path, 0, strlen($path) - strlen($filename));
		$service->setPath($dir);

		return $service->getTimestamp($filename);
	}
	
	
	protected function _getService() {
		return $this->_service;
	}
	
	
	protected function _setService() {
		if (!$this->_service) {
			$ini = $this->_getIni();
			$this->_service = new Garp_File_Storage_S3($ini->cdn);
		}
	}
}