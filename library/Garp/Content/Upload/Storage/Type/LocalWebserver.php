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

	/**
	 * @return Garp_Content_Upload_FileList
	 */
	public function fetchFileList() {
		$fileList = new Garp_Content_Upload_FileList();		
		$configuredPaths = $this->_getConfiguredPaths();

		foreach ($configuredPaths as $relPath) {
			$absPath = $this->_getAbsPath($relPath);
			if ($absPath !== false) {
				if (!($dirList = scandir($absPath))) {
					$this->_throwDirAccessError($absPath);
				}

				foreach ($dirList as $baseName) {
					$fileList->addEntry($relPath . '/' . $baseName);
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
		$absPath = $this->_getAbsPath($path);
		
		$md5output = exec("cat {$absPath} | md5sum");
		if ($md5output) {
			$md5output = str_replace(array(' ', '-'), '', $md5output);
			return $md5output;
		} else throw new Exception("Could not fetch md5 sum of {$path}.");
	}
	
	
	/**
	 * Fetches the contents of the given file.
	 * @param String $path 	Relative path to the file, starting with a slash.
	 * @return String		Content of the file. Throws an exception if file could not be read.
	 */
	public function fetchData($path) {
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
	 * @param String $path 	Relative path to the file, starting with a slash.
	 * @param String $data	File data to be stored.
	 * @return Boolean		Success of storage.
	 */
	public function store($path, $data) {
		$absPath = $this->_getBaseDir() . $path;

		$bytesWritten = file_put_contents($absPath, $data);
		if ($bytesWritten !== false) {
			return true;
		} else throw new Exception("Could not write to {$absPath} on " . $this->getEnvironment());
	}



	protected function _getAbsPath($relPath) {
		return realpath($this->_getBaseDir() . $relPath);
	}
	
	
	protected function _getBaseDir() {
		return APPLICATION_PATH . '/../public';
	}
}