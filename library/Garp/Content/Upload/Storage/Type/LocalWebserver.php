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

		$baseDir = $this->_getBaseDir();


		foreach ($configuredPaths as $relPath) {
			$absPath = $baseDir . $relPath;
			if (file_exists($absPath)) {
				if (!($dirList = scandir($absPath))) {
					$this->_throwDirAccessError($absPath);
				}

				foreach ($dirList as $baseName) {
					/**
					 * @todo: 	Dit net zo implementeren als RemoteWebserver,
					 * 			zodat je geen aparte filemtime hoeft te doen, maar de
					 *			ls-call in één keer uitleest.
					 */
					$fileList->addEntry($relPath . '/' . $baseName);
				}
			} else Garp_Cli::errorOut("Warning: {$absPath} does not exist.");			
		}
		
		return $fileList;
	}
	
	
	/**
	 * Calculate the eTag of a file.
	 * @param String $path 	Relative path to the file, starting with a slash.
	 * @return String 		Content hash (md5 sum of the content)
	 */
	public function fetchEtag($path) {
		$baseDir = $this->_getBaseDir();
		$absPath = $baseDir . $path;
		
		$md5output = exec("cat {$absPath} | md5sum");
		if ($md5output) {
			$md5output = str_replace(array(' ', '-'), '', $md5output);
			return $md5output;
		} else throw new Exception("Could not fetch md5 sum of {$path}.");
	}


	protected function _getBaseDir() {
		return realpath(APPLICATION_PATH . '/../public');
	}
}