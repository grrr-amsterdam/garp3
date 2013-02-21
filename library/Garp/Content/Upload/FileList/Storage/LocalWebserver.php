<?php
/**
 * Garp_Content_Upload_FileList_LocalWebserver
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
class Garp_Content_Upload_FileList_Storage_LocalWebserver extends Garp_Content_Upload_FileList_Abstract {

	protected function _buildList() {
		$configuredPaths = $this->_listConfiguredPaths();

		$baseDir = $this->_getBaseDir();
		

		foreach ($configuredPaths as $relPath) {
			$absPath = $baseDir . $relPath;
			if (file_exists($absPath)) {
				if (!($dirList = scandir($absPath))) {
					$this->_throwDirAccessError($absPath);
				}
			
				foreach ($dirList as $baseName) {
					$this->addEntry($relPath . '/' . $baseName);
				}
			} else Garp_Cli::errorOut("Warning: {$absPath} does not exist.");			
		}
	}
	
	
	protected function _getBaseDir() {
		return realpath(APPLICATION_PATH . '/../public');
	}
}