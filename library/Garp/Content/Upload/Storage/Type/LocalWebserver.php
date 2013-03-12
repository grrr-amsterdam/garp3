<?php
/**
 * Garp_Content_Upload_FileList_LocalWebserver
 * 
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
class Garp_Content_Upload_Storage_Type_LocalWebserver extends Garp_Content_Upload_Storage_Type_Abstract {
	const NEW_DIR_PERMISSIONS = 0774;


	/**
	 * @return Garp_Content_Upload_FileList
	 */
	public function fetchFileList() {
		$fileList = new Garp_Content_Upload_FileList();		
		$configuredPaths = $this->_getConfiguredPaths();

		foreach ($configuredPaths as $type => $relDir) {
			$absDir = $this->_getBaseDir() . $relDir;

			if (!file_exists($absDir)) {
				mkdir($absDir, self::NEW_DIR_PERMISSIONS, true);
			}

			if (!($dirList = scandir($absDir))) {
				$this->_throwDirAccessError($absDir);
			}

			foreach ($dirList as $baseName) {
				$fileList->addEntry($baseName, $type);
			}
		}
		
		return $fileList;
	}
	
	
	/**
	 * Calculate the eTag of a file.
	 * @param 	String $filename 	Filename
	 * @param 	String $type		File type, i.e. 'document' or 'image'
	 * @return 	String 				Content hash (md5 sum of the content)
	 */
	public function fetchEtag($filename, $path) {
		$absPath = $this->_getAbsPath($filename, $path);
		
		$md5output = exec("cat {$absPath} | md5sum");
		if ($md5output) {
			$md5output = str_replace(array(' ', '-'), '', $md5output);
			return $md5output;
		} else throw new Exception("Could not fetch md5 sum of {$path}.");
	}
	
	
	/**
	 * Fetches the contents of the given file.
	 * @param String $filename 	Filename
	 * @param String $type		File type, i.e. 'document' or 'image'
	 * @return String			Content of the file. Throws an exception if file could not be read.
	 */
	public function fetchData($filename, $type) {
		$absPath = $this->_getAbsPath($path);

		if ($absPath === false) {
			throw new Exception($absPath . ' does not exist');
		}
		
		$content = file_get_contents($absPath);
		if ($content !== false) {
			return $content;
		} else throw new Exception("Could not read {$absPath} on " . $this->getEnvironment());
	}
	
	
	/**
	 * Stores given data in the file, overwriting the existing bytes if necessary.
	 * @param String $filename 	Filename
	 * @param String $type		File type, i.e. 'document' or 'image'
	 * @param String $data		File data to be stored.
	 * @return Boolean			Success of storage.
	 */
	public function store($filename, $type, $data) {
		$absPath = $this->_getAbsPath($filename, $type);

		$bytesWritten = file_put_contents($absPath, $data);
		if ($bytesWritten !== false) {
			return true;
		} else throw new Exception("Could not write to {$absPath} on " . $this->getEnvironment());
	}

	/**
	 * @param 	String $filename 	Filename
	 * @param 	String $type		File type, i.e. 'document' or 'image'
	 * @return 	String				The absolute path to this file for use on the local file system
	 */
	protected function _getAbsPath($filename, $type) {
		$baseDir 		= $this->_getBaseDir();
		$absPath 		= $this->_getBaseDir() . $this->_getRelPath($filename, $type);
		return $absPath;
	}
	
	
	protected function _getBaseDir() {
		return APPLICATION_PATH . '/../public';
	}
}