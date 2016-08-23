<?php
/**
 * @package Tests
 * @author  Harmen Janssen <harmen@grrr.nl>
 * @group File
 */
class Garp_File_ExtensionTest extends Garp_Test_PHPUnit_TestCase {
    protected $_mockPngFile = '/../public/css/images/favicon.png';

    public function testShouldGetTheExtensionCorrectly() {
        $ext = new Garp_File_Extension('image/jpeg');
        $this->assertEquals('jpg', $ext->getValue());
        $this->assertEquals('jpg', (string)$ext->getValue());
    }

    public function testShouldGetTheExtensionForLiveObject() {
        // Use finfo for a real-live example
        $finfo = new finfo(FILEINFO_MIME);
        $mime = $finfo->file(GARP_APPLICATION_PATH . $this->_mockPngFile);

        $mime = explode(';', $mime);
        $mime = $mime[0];

        $ext = new Garp_File_Extension($mime);
        $this->assertEquals('png', $ext->getValue());
    }

    public function testShouldReturnNullForUnknownExtension() {
        $ext = new Garp_File_Extension('cookie/chocolate-chip');
        $this->assertTrue(is_null($ext->getValue()));
    }

}
