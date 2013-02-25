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
					$fileList->addEntry($relPath . '/' . $baseName, filemtime($absPath));
				}
			} else Garp_Cli::errorOut("Warning: {$absPath} does not exist.");			
		}
		
		return $fileList;
	}


	protected function _getBaseDir() {
		return realpath(APPLICATION_PATH . '/../public');
	}
}