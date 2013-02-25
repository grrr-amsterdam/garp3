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
	protected $_s3;


	public function __construct($environment) {
		parent::__construct($environment);
		$this->_setS3();
	}


	public function fetchFileList() {
		$fileList = new Garp_Content_Upload_FileList();

		$s3 = $this->_getS3();

		$uploadTypePaths = $this->_getConfiguredPaths();
		
		foreach ($uploadTypePaths as $dirPath) {
			$s3->setPath($dirPath);
			$dirList = $s3->getList();
			
			foreach ($dirList as $filePath) {
				if ($filePath[strlen($filePath) - 1] !== '/') {
					$fileList->addEntry('/' . $filePath);
				}
			}
		}

		return $fileList;
	}
	
	
	protected function _getS3() {
		return $this->_s3;
	}
	
	
	protected function _setS3() {
		if (!$this->_s3) {
			$ini = $this->_getIni();
			$this->_s3 = new Garp_File_Storage_S3($ini->cdn);
		}
	}
}