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
		$fileList 			= new Garp_Content_Upload_FileList();
		$service 			= $this->_getService();
		$uploadTypePaths 	= $this->_getConfiguredPaths();
		
		foreach ($uploadTypePaths as $type => $dirPath) {
			$service->setPath($dirPath);
			$dirList 		= $service->getList();
			$fileListByType = $this->_findFilesByType($dirList, $type);
			$fileList->addEntries($fileListByType);
		}

		return $fileList;
	}
	
	
	/**
	 * @param Array 	$dirList	Array of file paths
	 * @param String 	$type		Upload type
	 */
	protected function _findFilesByType(array $dirList, $type) {
		$fileList = new Garp_Content_Upload_FileList();

		foreach ($dirList as $path) {
			if ($this->_isFilePath($path)) {
				$baseName = basename($path);
				$fileNode = new Garp_Content_Upload_FileNode($baseName, $type);
				$fileList->addEntry($fileNode);
			}
		}
		
		return $fileList;
	}
	
	
	protected function _isFilePath($path) {
		return $path[strlen($path) - 1] !== '/';
	}


	/**
	 * Calculate the eTag of a file.
	 * @param 	String $filename 	Filename
	 * @param 	String $type		File type, i.e. 'document' or 'image'
	 * @return 	String 				Content hash (md5 sum of the content)
	 */
	public function fetchEtag($filename, $type) {
		$relPath 	= $this->_getRelPath($filename, $type);
		$dir 		= $this->_getRelDir($relPath);
		$service 	= $this->_getService();

		$service->setPath($dir);

		return $service->getEtag($filename);
	}


	/**
	 * Fetches the contents of the given file.
	 * @param 	String $filename 	Filename
	 * @param 	String $type		File type, i.e. 'document' or 'image'
	 * @return 	String				Content of the file. Throws an exception if file could not be read.
	 */
	public function fetchData($filename, $type) {
		$relPath	= $this->_getRelPath($filename, $type);
		$ini 		= $this->_getIni();
		$cdnDomain 	= $ini->cdn->domain;
		$url 		= 'http://' . $cdnDomain . $relPath;

		$content = file_get_contents($url);
		if ($content !== false) {
			return $content;
		} else throw new Exception("Could not read {$url} on " . $this->getEnvironment());
	}
	
	
	/**
	 * Stores given data in the file, overwriting the existing bytes if necessary.
	 * @param 	String $filename 	Filename
	 * @param 	String $type		File type, i.e. 'document' or 'image'
	 * @param 	String $data		File data to be stored.
	 * @return 	Boolean				Success of storage.
	 */
	public function store($filename, $type, $data) {
		$dir 		= $this->_getRelDir($path);
		$service 	= $this->_getService();

		$service->setPath($dir);
		return $service->store($filename, $data, true, false);
	}


	/**
	 * @param 	String $path 	Relative path to the file.
	 * @return 	String 			The relative path to the directory where the file resides.
	 */
	protected function _getRelDir($path) {
		$filename = basename($path);
		$dir = substr($path, 0, strlen($path) - strlen($filename));
		return $dir;
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