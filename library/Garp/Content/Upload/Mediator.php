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
	
	
	public function fetchDiff() {
		$sourceList = $this->_source->fetchFileList();
		$targetList = $this->_target->fetchFileList();

		$newFiles = $this->_findNewFiles($sourceList, $targetList);

		$conflictingFiles = $this->_findConflictingFiles($sourceList, $targetList);
		
		
		Zend_Debug::dump($conflictingFiles);
		exit;
		//............
	}
	
	
	/**
	 * @return Array Numeric array of file paths, referring to files that are new to the target environment.
	 */
	protected function _findNewFiles(Garp_Content_Upload_FileList $sourceList, Garp_Content_Upload_FileList $targetList) {
		$newFiles = array();

		foreach ($sourceList as $sourceFile) {
			$matchFound = false;

			foreach ($targetList as $targetFile) {
				if ($sourceFile['path'] === $targetFile['path']) {
					$matchFound = true;
					break;
				}
			}
			
			if (!$matchFound) {
				$newFiles[] = $sourceFile['path'];
			}
		}
		
		return $newFiles;
	}


	/**
	 * @return Array 	Numeric array of file paths, referring to source files that exist on the target environment, but have a
	 *					different last modified timestamp.
	 */
	protected function _findConflictingFiles(Garp_Content_Upload_FileList $sourceList, Garp_Content_Upload_FileList $targetList) {
		$conflictingFiles = array();

		foreach ($sourceList as $sourceFile) {
			foreach ($targetList as $targetFile) {
				if ($sourceFile['path'] === $targetFile['path']) {
					/**
					* @todo IF LAST MODIFIED IS ANDERS
					*/
					if (is_null($sourceFile['lastmodified'])) {
						$sourceFile['lastmodified'] = $this->_source->findLastModified($sourceFile['path']);
					}
					
					if (is_null($targetFile['lastmodified'])) {
						$targetFile['lastmodified'] = $this->_target->findLastModified($targetFile['path']);
					}

					if ($sourceFile['lastmodified'] != $targetFile['lastmodified']) {
						$conflictingFiles[] = $sourceFile['path'];
					}
					/**
					 * @todo: Moet dit toch met etags? Want last modification date lijkt wel altijd te veranderen...
					 */
					break;
				}
			}
		}

		return $conflictingFiles;
	}


}
