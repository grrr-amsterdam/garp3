<?php
/**
 * Garp_Content_Upload_Mediator
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
class Garp_Content_Upload_Mediator {

	/**
	 * Garp_Content_Upload_Storage_Protocol $source
	 */
	protected $_source;

	/**
	 * Garp_Content_Upload_Storage_Protocol $target
	 */
	protected $_target;


	/**
	 * @param String $sourceEnv
	 * @param String $targetEnv
	 */
	public function __construct($sourceEnv, $targetEnv) {
		$this->setSource($sourceEnv);
		$this->setTarget($targetEnv);
	}


	/**
	 * @param String $environment
	 */
	public function setSource($environment) {
		$this->_source = Garp_Content_Upload_Storage_Factory::create($environment);
	}


	/**
	 * @param String $environment
	 */
	public function setTarget($environment) {
		$this->_target = Garp_Content_Upload_Storage_Factory::create($environment);
	}
	
	
	/**
	 * Finds out which files should be transferred.
	 * @return Garp_Content_Upload_FileList List of file paths that should be transferred from source to target.
	 */
	public function fetchDiff() {
		$diffList = new Garp_Content_Upload_FileList();
		
		$sourceList = $this->_source->fetchFileList();
		$targetList = $this->_target->fetchFileList();

		$newFiles = $this->_findNewFiles($sourceList, $targetList);

		$conflictingFiles = $this->_findConflictingFiles($sourceList, $targetList);

		$diffList->addEntries($newFiles);
		$diffList->addEntries($conflictingFiles);

		return $diffList;
	}
	
	
	public function transfer(Garp_Content_Upload_FileList $fileList) {
		foreach ($fileList as $filePath) {
			$fileData = $this->_source->fetchData($filePath);
			if (!$this->_target->store($filePath, $fileData)) {
				throw new Exception("Could not store {$filePath} on " . $this->_target->getEnvironment());
			}
		}
	}
	
	
	/**
	 * @return Array Numeric array of file paths, referring to files that are new to the target environment.
	 */
	protected function _findNewFiles(Garp_Content_Upload_FileList $sourceList, Garp_Content_Upload_FileList $targetList) {
		$newFiles = array();

		foreach ($sourceList as $sourceFile) {
			$matchFound = false;

			foreach ($targetList as $targetFile) {
				if ($sourceFile === $targetFile) {
					$matchFound = true;
					break;
				}
			}
			
			if (!$matchFound) {
				$newFiles[] = $sourceFile;
			}
		}
		
		return $newFiles;
	}


	/**
	 * @return Array 	Numeric array of file paths, referring to source files that exist on the target environment, but have
	 *					different content.
	 */
	protected function _findConflictingFiles(Garp_Content_Upload_FileList $sourceList, Garp_Content_Upload_FileList $targetList) {
		$conflictingFiles = array();

		foreach ($sourceList as $sourceFile) {
			foreach ($targetList as $targetFile) {
				if ($sourceFile === $targetFile) {

					$sourceEtag = $this->_source->fetchEtag($sourceFile);
					$targetEtag = $this->_target->fetchEtag($targetFile);

					if ($sourceEtag != $targetEtag) {
						$conflictingFiles[] = $sourceFile;
					}

					break;
				}
			}
		}

		return $conflictingFiles;
	}


}
