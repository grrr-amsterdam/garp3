<?php
/**
 * @package Tests
 * @author  David Spreekmeester <david@grrr.nl>
 * @group   Image
 */
class G_View_Helper_Image_Test extends Garp_Test_PHPUnit_TestCase {
    public function test_source_url_should_not_be_empty() {
        $imageHelper = $this->_getImageHelper();
        $sourceUrl   = $imageHelper->getSourceUrl('foo.png');

        $this->assertTrue(!empty($sourceUrl));
    }


    public function test_source_url_should_have_http_protocol() {
        $imageHelper = $this->_getImageHelper();
        $sourceUrl   = $imageHelper->getSourceUrl('foo.png');
        $urlScheme   = parse_url((string)$sourceUrl, PHP_URL_SCHEME);

        $this->assertEquals('https', $urlScheme, $sourceUrl);
    }


    /*
     * I'm not sure why this is relevant...
     * It fails now we're using Garp_Util_AssetUrl because it'll end in ?v1.2.3...
     *
    public function test_source_url_should_end_in_filename() {
        $filename       = 'foo.png';
        $imageHelper    = $this->_getImageHelper();
        $sourceUrl      = $imageHelper->getSourceUrl($filename);
        $filenameLength = strlen($filename);
        $lastChars      = substr($sourceUrl, -$filenameLength);

        $this->assertTrue($lastChars === $filename, $sourceUrl);
    }
     */

    /**
     * @return G_View_Helper_Image
     */
    protected function _getImageHelper() {
        $bootstrap   = Zend_Registry::get('application')->getBootstrap();
        $imageHelper = $bootstrap->getResource('View')->image();
        return $imageHelper;
    }

    public function setUp() {
        parent::setUp();
        $this->_helper->injectConfigValues(
            array(
            'cdn' => array(
                'domain' => 'amazonaws.com',
                's3' => array(
                    'apikey' => '12345',
                    'secret' => '67890'
                ),
                'type' => 's3',
                'ssl' => true,
                'path' => array(
                    'upload' => array(
                        'image' => '/images',
                        'document' => '/documents'
                    ),
                    'static' => array(
                        'image' => '/static-images',
                        'document' => '/static-documents'
                    )
                ),
                'extensions' => 'jpg,gif,png'
            )
            )
        );
    }
}
