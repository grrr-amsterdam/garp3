<?php
/**
 * Garp_Content_CDN_AssetList
 * You can use an instance of this class as a numeric array, containing the paths to the selected assets.
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
class Garp_Content_CDN_AssetList extends ArrayObject {
	
	protected $_bannedNodeSubstrings = array('.php', '.psd', 'uploads', 'cached', 'sass');
	
	protected $_baseDir;
	protected $_baseDirLength;
	
	// protected $_filterString;
	/**
	 * A timestamp to be used as a filter for the file age.
	 */
	protected $_fileAgeThreshold;
	
	const ERROR_CANT_OPEN_DIRECTORY = "Unable to open the configuration directory: %s";
	const HIDDEN_FILES_PREFIX = '.';
	
	/**
	 * Default age of files that are relevant for distribution.
	 */
	const DEFAULT_FILE_AGE_THRESHOLD = '-2 weeks';
	const NEGATIVE_FILE_AGE_THRESHOLD = '-100 years';
	
	
	
	/**
	 * @param	$baseDir			The base directory
	 * @param	$filterString		A search string to filter filenames by
	 * @param	$fileAgeThreshold	An age threshold to use as a file filter, excluding old files for distribution.
	 *								This should be in a format that can be fed to strtotime().
	 *								Defaults to self::DEFAULT_FILE_AGE_THRESHOLD. Can be set to false to disable the filter.
	 */
	public function __construct($baseDir, $filterString = null, $fileAgeThreshold = null) {
		$distributor 				= new Garp_Content_CDN_Distributor();
		$this->_baseDir				= $baseDir;
		$this->_baseDirLength		= strlen($baseDir);
		
		// $this->_filterString		= $filterString;
		$this->_fileAgeThreshold	= $this->_setFileAgeThreshold($fileAgeThreshold);
		
		$this->_setFileAgeThreshold($fileAgeThreshold);
		$this->_crawlDirectory($filterString, $baseDir);
	}
	
	
	protected function _setFileAgeThreshold($fileAgeThreshold) {
		if (is_null($fileAgeThreshold)) {
			$relThreshold = self::DEFAULT_FILE_AGE_THRESHOLD;
		} elseif ($fileAgeThreshold === false) {
			$relThreshold = self::NEGATIVE_FILE_AGE_THRESHOLD;
		} else {
			$relThreshold = $fileAgeThreshold;
		}

		$this->_fileAgeThreshold = strtotime($relThreshold);
	}
	
	
	protected function _crawlDirectory($filterString, $dir) {
		if (!($dirList = scandir($dir))) {
			$this->_throwDirAccessError($dir);
		}

		$validDirList = array_filter($dirList, array($this, '_isValidAssetName'));

		foreach ($validDirList as $nodeName) {
			$nodePathAbs = $dir . DIRECTORY_SEPARATOR . $nodeName;

			is_dir($nodePathAbs) 
				? $this->_crawlDirectory($filterString, $nodePathAbs)
				: $this->_addValidAssetFile($nodeName, $nodePathAbs, $filterString)
			;			
		}
	}
	
	
	protected function _throwDirAccessError($dir) {
		$errorMsg = sprintf(self::ERROR_CANT_OPEN_DIRECTORY, $dir);
		throw new Exception($errorMsg);
	}



	protected function _addValidAssetFile($fileName, $filePathAbs, $filterString) {
		$filePathRel = substr($filePathAbs, $this->_baseDirLength);

		if (
			(!$filterString || stripos($filePathRel, $filterString) !== false) &&
			$this->_isWithinTimeFrame($filePathAbs)
		) {
			$this[] = $filePathRel;
		}
	}
	
	
	protected function _isValidAssetName($nodeName) {
		return !(
			$nodeName[0] === self::HIDDEN_FILES_PREFIX ||
			$this->_isBannedAssetName($nodeName)
		);
	}
	
	
	protected function _isWithinTimeFrame($filePathAbs) {
		$fileTimestamp 	= filemtime($filePathAbs);

		return $fileTimestamp >= $this->_fileAgeThreshold;
	}
	
	
	protected function _isBannedAssetName($nodeName) {
		$isBanned = false;
		
		foreach ($this->_bannedNodeSubstrings as $bannedString) {
			$isMatching = stripos($nodeName, $bannedString) !== false;
			$isBanned 	= $isBanned ?: $isMatching;
		}
		
		return $isBanned;
	}
}