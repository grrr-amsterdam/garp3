<?php
/**
 * @author David Spreekmeester | Grrr.nl
 */
class Garp_Image_PngQuantTest extends PHPUnit_Framework_TestCase {
	const RESOURCE_UNOPTIMIZED_PNG =
		'/../tests/files/images/unoptimized.png';


	public function testCanOptimizePng() {
		$pngQuant = new Garp_Image_PngQuant();
		if (!$pngQuant->isAvailable()) {
			return;
		}

		$sourcePath = $this->_getMockImagePath();
		$sourceData = file_get_contents($sourcePath);

		$targetData = $pngQuant->optimizeData($sourceData);

		$this->assertLessThan(strlen($sourceData), strlen($targetData));
	}

	protected function _getMockImagePath() {
		return GARP_APPLICATION_PATH . DIRECTORY_SEPARATOR . self::RESOURCE_UNOPTIMIZED_PNG;
	}
}
