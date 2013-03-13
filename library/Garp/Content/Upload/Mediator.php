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
		$progress = Garp_Cli_Ui_ProgressBar::getInstance();		
		$diffList = new Garp_Content_Upload_FileList();

		$sourceList = $this->_source->fetchFileList();
		// Zend_Debug::dump($sourceList);
		// exit;
		$targetList = $this->_target->fetchFileList();

		$progress->display("Looking for new files");
		$newFiles = $this->_findNewFiles($sourceList, $targetList);

		$progress->display("Looking for conflicting files");
		$conflictingFiles = $this->_findConflictingFiles($sourceList, $targetList);

		$progress->display("√ Done comparing.");

		$diffList->addEntries($newFiles);
		$diffList->addEntries($conflictingFiles);

		return $diffList;
	}
	
	
	public function transfer(Garp_Content_Upload_FileList $fileList) {
		$progress = Garp_Cli_Ui_ProgressBar::getInstance();

		foreach ($fileList as $file) {
			$filename 	= $file->getFilename();
			$type 		= $file->getType();
Zend_Debug::dump($file);
exit;
			$progress->display("Fetching {$filename}");
			$fileData = $this->_source->fetchData($filename, $type);
			$progress->advance();

			$progress->display("Uploading {$filename}");

			if ($this->_target->store($filename, $type, $fileData)) {
				$progress->advance();
			} else {
				throw new Exception("Could not store {$type} {$filename} on " . $this->_target->getEnvironment());
			}
		}
	}
	
	
	/**
	 * @return Array Numeric array of files, with keys 'filename' and 'type'.
	 */
	protected function _findNewFiles(Garp_Content_Upload_FileList $sourceList, Garp_Content_Upload_FileList $targetList) {
		$unique = $sourceList->findUnique($targetList);
		return $unique;
	}


	/**
	 * @return Garp_Content_Upload_FileList Conflicting files
	 */
	protected function _findConflictingFiles(Garp_Content_Upload_FileList $sourceList, Garp_Content_Upload_FileList $targetList) {
		$existingFiles 		= $sourceList->findIntersecting($targetList);
		$conflictingFiles 	= $this->_findConflictingFilesByEtag($existingFiles);

		return $conflictingFiles;
	}

	protected function _findConflictingFilesByEtag(Garp_Content_Upload_FileList $conflictsByName) {
		$progress = Garp_Cli_Ui_ProgressBar::getInstance();
		$progress->init(count($conflictsByName));

		$conflictsByEtag = new Garp_Content_Upload_FileList();
		
		foreach ($conflictsByName as $file) {
			$this->_addEtagConflictingFile($conflictsByEtag, $file);
		}

		return $conflictsByEtag;
	}

	/**
	 * @param	Garp_Content_Upload_FileList	&$conflictsByEtag	A reference to the list that a matching entry should be added to
	 * @param	Garp_Content_Upload_FileNode	$file	The current file node within the Garp_Content_Upload_FileList
	 * @return 	Void
	 */
	protected function _addEtagConflictingFile(Garp_Content_Upload_FileList &$conflictsByEtag, Garp_Content_Upload_FileNode $file) {
		$progress = Garp_Cli_Ui_ProgressBar::getInstance();
		$filename	= $file->getFilename();
		$progress->display("Comparing {$filename}");

		if (!$this->_matchEtags($file)) {
			$conflictsByEtag->addEntry($file);
		}

		$progress->advance();
	}
	
	
	protected function _matchEtags(Garp_Content_Upload_FileNode $file) {
		$filename	= $file->getFilename();
		$type		= $file->getType();

		$sourceEtag = $this->_source->fetchEtag($filename, $type);
		$targetEtag = $this->_target->fetchEtag($filename, $type);

		return $sourceEtag == $targetEtag;
	}

}