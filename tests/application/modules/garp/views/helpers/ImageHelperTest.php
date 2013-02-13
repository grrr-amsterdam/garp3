<?php
/**
 * @group Image
 */
class G_View_Helper_Image_Test extends PHPUnit_Framework_TestCase {
	public function test_source_url_should_not_be_empty() {
		$imageHelper 	= $this->_getImageHelper();
		$sourceUrl 		= $imageHelper->getSourceUrl('foo.png');
		
		$this->assertTrue(!empty($sourceUrl));
	}


	public function test_source_url_should_have_http_protocol() {
		$imageHelper 	= $this->_getImageHelper();
		$sourceUrl 		= $imageHelper->getSourceUrl('foo.png');
		$first7chars	= substr($sourceUrl, 0, 7);

		$this->assertTrue($first7chars === 'http://');
	}


	public function test_source_url_should_end_in_filename() {
		$filename		= 'foo.png';
		$imageHelper 	= $this->_getImageHelper();
		$sourceUrl 		= $imageHelper->getSourceUrl($filename);
		$filenameLength	= strlen($filename);
		$lastChars		= substr($sourceUrl, -$filenameLength);

		$this->assertTrue($lastChars === $filename);
	}
	
	
	protected function _getImageHelper() {
        $bootstrap 		= Zend_Registry::get('application')->getBootstrap();
        $imageHelper 	= $bootstrap->getResource('View')->image();
		return $imageHelper;
	}
	
}