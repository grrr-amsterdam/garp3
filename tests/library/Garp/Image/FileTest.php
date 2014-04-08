<?php
/**
 * @author David Spreekmeester | Grrr.nl
 * This class tests Garp_Image_File.
 */
class Garp_Image_FileTest extends PHPUnit_Framework_TestCase {
	const RESOURCE_UNOPTIMIZED_PNG = '/../garp/tests/application/modules/mocks/resources/images/unoptimized.png';
	
	protected $_bogusValidImageFilenames = array(
		'$(4g3s#@!)@#%(#@√£¡).JPEG',
		'poesje-4.jpg',
		'émerfep¢9£ª^ƒ˚ßƒ´¬å⌛✇☠❄☺☹♨ ♩ ✙✈ ✉✌ ✁ ✎ ✐ ❀ ✰ ❁ ❤ ❥ ❦❧ ➳ ➽ εїз℡❣·۰•●○●.gif',
		'33.png'
	);

	
	public function testGetImageType() {
		$imageFile = new Garp_Image_File();

		foreach ($this->_bogusValidImageFilenames as $filename) {
			$imageType = $imageFile->getImageType($filename);

			$this->assertTrue(
				$imageType === IMAGETYPE_GIF ||
				$imageType === IMAGETYPE_JPEG ||
				$imageType === IMAGETYPE_PNG
			);
		}
	}
	
	public function testCanStorePngsOptimized() {
		$imageFile = new Garp_Image_File();

		$sourcePath 	= $this->_getMockImagePath();
		$sourceData 	= $this->_getMockImageData($sourcePath);
		$sourceSize 	= strlen($sourceData);
		$sourceFilename = basename($sourcePath);
		
		$destinationFilename 	= $imageFile->store($sourceFilename, $sourceData);
		$destinationData 		= $imageFile->fetch($destinationFilename);
		$destinationSize 		= strlen($destinationData);

		$this->assertNotEquals($destinationFilename, false);
		$this->assertLessThan($sourceSize, $destinationSize);
	}

	protected function _getMockImagePath() {
		return APPLICATION_PATH . self::RESOURCE_UNOPTIMIZED_PNG;
	}

	protected function _getMockImageData() {
		$path = $this->_getMockImagePath();
		return file_get_contents($path);
	}

	// function testFormatFilenameShouldReturnNonEmpty() {
	// 	foreach ($this->_bogusFilenames as $origFilename) {
	// 		$newFilename = Garp_File::formatFilename($origFilename);
	// 		$this->assertTrue(
	// 			!empty($newFilename),
	// 			"Original filename: [{$origFilename}], new filename: [{$newFilename}]"
	// 		);
	// 	}
	// }
	// 
	// 
	// function testFormatFilenameShouldContainNoneOrASingleDot() {
	// 	foreach ($this->_bogusFilenames as $origFilename) {
	// 		$newFilename = Garp_File::formatFilename($origFilename);
	// 		$this->assertTrue(
	// 			substr_count($newFilename, '.') <= 1,
	// 			"Original filename: [{$origFilename}], new filename: [{$newFilename}]"
	// 		);
	// 	}
	// }
	// 
	// 
	// function testFormatFilenameShouldContainOnlyPlainCharacters() {
	// 	foreach ($this->_bogusFilenames as $origFilename) {
	// 		$newFilename = Garp_File::formatFilename($origFilename);
	// 		$this->assertTrue(
	// 			$this->_containsOnlyPlainCharacters($newFilename),
	// 			"Original filename: [{$origFilename}], new filename: [{$newFilename}]"
	// 		);
	// 	}
	// }
	// 
	// 
	// function testGetCumulativeFilenameShouldReturnNonEmpty() {
	// 	foreach ($this->_bogusFilenames as $origFilename) {
	// 		$newFilename = Garp_File::getCumulativeFilename($origFilename);
	// 		$this->assertTrue(
	// 			!empty($newFilename),
	// 			"Original filename: [{$origFilename}], new filename: [{$newFilename}]"
	// 		);
	// 	}
	// }
	// 
	// 
	// function testGetCumulativeFilenameShouldContainNoneOrASingleDot() {
	// 	foreach ($this->_bogusFilenames as $origFilename) {
	// 		$newFilename = Garp_File::getCumulativeFilename($origFilename);
	// 		$this->assertTrue(
	// 			substr_count($newFilename, '.') <= 1,
	// 			"Original filename: [{$origFilename}], new filename: [{$newFilename}]"
	// 		);
	// 	}
	// }
	// 
	// 
	// function testGetCumulativeFilenameShouldContainOnlyPlainCharacters() {
	// 	foreach ($this->_bogusFilenames as $origFilename) {
	// 		$newFilename = Garp_File::getCumulativeFilename($origFilename);
	// 		$this->assertTrue(
	// 			$this->_containsOnlyPlainCharacters($newFilename),
	// 			"Original filename: [{$origFilename}], new filename: [{$newFilename}]"
	// 		);
	// 	}
	// }
	// 
	// 
	// /**
	//  * Checks whether the argument provided contains only word characters (a to z, A to Z, 0 to 9 or underscores), dashes and dots. This excludes characters with accents.
	//  * @param String $filename The filename to be checked
	//  * @return Boolean Whether the provided argument contains only plain characters.
	//  */
	// protected function _containsOnlyPlainCharacters($filename) {
	// 	return preg_match('/[^\w-\.]/', $filename) === 0;
	// }
}