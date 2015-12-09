<?php
/**
 * Garp_Content_Cdn_AssetList
 * You can use an instance of this class as a numeric array, containing the paths to the selected assets.
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @group Cdn
 * @lastmodified $Date: $
 */
class Garp_Content_Cdn_AssetList_Test extends PHPUnit_Framework_TestCase {
	const FILTER_STRING_MATCHING_MULTIPLE 	= 'css';
	const FILTER_STRING_MATCHING_ONE_GARP 	= 'css/garp/images/garp.png';
	const FILTER_STRING_MATCHING_ONE_APP 	= 'cms.css';
	const FILTER_STRING_NOT_MATCHING 		= 'l3$#j@[hdv%@u2w2a9g08u.e3#d@c';
	const FILE_TIMESTAMP_THRESHOLD 			= '-2 weeks';
	const TEST_FILENAME                     = 'tmp_file_Garp_Content_Cdn_AssetList_Test_tmp_file';

	public function test_Base_Dir_Should_Not_Be_Empty() {
		$baseDir = $this->_getBaseDir();
		$this->assertNotSame(strlen($baseDir), 0);
	}

	public function test_No_Assets_Should_Be_Selected_If_No_Match() {
		$assetList	= $this->_getListInstance(self::FILTER_STRING_NOT_MATCHING);
		$this->assertSame(count($assetList), 0);
	}

	public function test_Multiple_Assets_Should_Be_Selected_If_Match() {
		$this->_addTmpFile();

		$assetList = $this->_getListInstance(self::FILTER_STRING_MATCHING_MULTIPLE);
		$this->assertTrue((bool)count($assetList), 'Assetlist length is actually: ' . count($assetList));

		// cleanup
		$this->_rmTmpFile();
	}

	public function test_One_Garp_Asset_Should_Be_Selected_If_Specific_Match() {
		$assetList	= $this->_getListInstance(self::FILTER_STRING_MATCHING_ONE_GARP, false);
		$this->assertSame(count($assetList), 1);
	}

	public function test_One_App_Asset_Should_Be_Selected_If_Specific_Match() {
		$assetList	= $this->_getListInstance(self::FILTER_STRING_MATCHING_ONE_APP, false);
		$this->assertSame(count($assetList), 1);
	}

	public function test_Assets_Paths_Should_Be_Relative() {
		$this->_addTmpFile();

		$assetList = $this->_getListInstance(self::FILTER_STRING_MATCHING_MULTIPLE);
		$this->assertTrue((bool)count($assetList), 'Assetlist length is actually: ' . count($assetList));
		$this->assertTrue(strpos($assetList[0], $this->_getBaseDir()) === false);

		// cleanup
		$this->_rmTmpFile();
	}

	public function test_Assets_Should_Not_Be_Older_Than_Threshold_If_No_Params_Given() {
		$assetList	= $this->_getListInstance(self::FILTER_STRING_MATCHING_MULTIPLE);

		foreach ($assetList as $assetPathRel) {
			$assetPathAbs 	= $this->_getBaseDir() . $assetPathRel;
			$fileTimestamp 	= filemtime($assetPathAbs);
			$threshold		= strtotime(self::FILE_TIMESTAMP_THRESHOLD);

			$this->assertTrue(
				$fileTimestamp >= $threshold,
				"Timestamp of {$assetPathRel}: " . strftime('%d-%m-%Y', $fileTimestamp)
				. ', should be: now ' . self::FILE_TIMESTAMP_THRESHOLD
			);
		}
	}

	protected function _getBaseDir() {
		$distributor = new Garp_Content_Cdn_Distributor();
		return $distributor->getBaseDir();
	}

	protected function _getListInstance($filterString, $filterByFileDate = null) {
		return new Garp_Content_Cdn_AssetList($this->_getBaseDir(), $filterString, $filterByFileDate);
	}

	// Make sure a changed file is present
	protected function _addTmpFile() {
		$baseDir = $this->_getBaseDir();
		$tmpFilePath = $baseDir . DIRECTORY_SEPARATOR . self::TEST_FILENAME . self::FILTER_STRING_MATCHING_MULTIPLE;
		file_put_contents($tmpFilePath, uniqid());
	}

	// Cleanup tmp file
	protected function _rmTmpFile() {
		$baseDir = $this->_getBaseDir();
		$tmpFilePath = $baseDir . DIRECTORY_SEPARATOR . self::TEST_FILENAME . self::FILTER_STRING_MATCHING_MULTIPLE;
		unlink($tmpFilePath);
	}
}
