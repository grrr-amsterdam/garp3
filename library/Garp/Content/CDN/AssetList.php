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
	
	protected $_bannedNodeSubstrings = array('.php', '.psd');
	
	protected $_bannedNodeNames = array('uploads', 'cached', 'sass', 'system', 'pids', 'log');

	protected $_baseDir;
	protected $_baseDirLength;
	
	protected $_filterString;
	
	/**
	 * A timestamp to be used as a filter for the file age.
	 */
	protected $_filterDate;
	
	const ERROR_CANT_OPEN_DIRECTORY = "Unable to open the configuration directory: %s";
	const HIDDEN_FILES_PREFIX = '.';
	
	/**
	 * Default age of files that are relevant for distribution.
	 */
	const DEFAULT_FILTER_DATE = '-2 weeks';
	const NEGATIVE_FILTER_DATE = '-100 years';
	
	
	
	/**
	 * @param	$baseDir			The base directory
	 * @param	$filterString		A search string to filter filenames by
	 * @param	$filterDate	An age threshold to use as a file filter, excluding old files for distribution.
	 *								This should be in a format that can be fed to strtotime().
	 *								Defaults to self::DEFAULT_FILTER_DATE. Can be set to false to disable the filter.
	 */
	public function __construct($baseDir, $filterString = null, $filterDate = null) {
		$this->_baseDir				= $baseDir;
		$this->_baseDirLength		= strlen($baseDir);
		
		$this->_filterString		= $filterString;
		$this->_filterDate			= $this->_setFilterDate($filterDate);

		$this->_crawlDirectory($baseDir);
	}
	
	
	public function getFilterDate() {
		return $this->_filterDate;
	}
	
	
	protected function _setFilterDate($filterDate) {
		if (is_null($filterDate)) {
			$relThreshold = self::DEFAULT_FILTER_DATE;
		} elseif ($filterDate === false) {
			$relThreshold = self::NEGATIVE_FILTER_DATE;
		} else {
			$relThreshold = $filterDate;
		}

		return strtotime($relThreshold);
	}
	
	
	protected function _crawlDirectory($dir) {
		if (!($dirList = scandir($dir))) {
			$this->_throwDirAccessError($dir);
		}

		$validDirList = array_filter($dirList, array($this, '_isValidAssetName'));

		foreach ($validDirList as $nodeName) {
			$nodePathAbs = $dir . DIRECTORY_SEPARATOR . $nodeName;

			is_dir($nodePathAbs) 
				? $this->_crawlDirectory($nodePathAbs)
				: $this->_addValidAssetFile($nodeName, $nodePathAbs)
			;			
		}
	}
	
	
	protected function _throwDirAccessError($dir) {
		$errorMsg = sprintf(self::ERROR_CANT_OPEN_DIRECTORY, $dir);
		throw new Exception($errorMsg);
	}



	protected function _addValidAssetFile($fileName, $filePathAbs) {
		$filePathRel = substr($filePathAbs, $this->_baseDirLength);

		if (
			(!$this->_filterString || stripos($filePathRel, $this->_filterString) !== false) &&
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

		return $fileTimestamp >= $this->_filterDate;
	}
	
	
	protected function _isBannedAssetName($nodeName) {
		$isBanned = false;
		
		foreach ($this->_bannedNodeSubstrings as $bannedString) {
			if (stripos($nodeName, $bannedString) !== false) {
				return true;
			}
		}
		
		if (in_array($nodeName, $this->_bannedNodeNames)) {
			return true;
		}

		return false;
	}
}