<?php
/**
 * This class tests Garp_File.
 *
 * @package Tests
 * @author  David Spreekmeester <david@grrr.nl>
 * @group   File
 */
class Garp_FileTest extends Garp_Test_PHPUnit_TestCase {
    protected $_bogusFilenames = array(
        '$(4g3s#@!)@#%(#@√£¡).JPEG',
        'émerfep¢9£ª^ƒ˚ßƒ´¬å⌛✇☠❄☺☹♨ ♩ ✙✈ ✉✌ ✁ ✎ ✐ ❀ ✰ ❁ ❤ ❥ ❦❧ ➳ ➽ εїз℡❣·۰•●○●',
        '.htaccess',
        ''
    );

    function testFormatFilenameShouldReturnNonEmpty() {
        foreach ($this->_bogusFilenames as $origFilename) {
            $newFilename = Garp_File::formatFilename($origFilename);
            $this->assertTrue(
                !empty($newFilename),
                "Original filename: [{$origFilename}], new filename: [{$newFilename}]"
            );
        }
    }


    function testFormatFilenameShouldContainNoneOrASingleDot() {
        foreach ($this->_bogusFilenames as $origFilename) {
            $newFilename = Garp_File::formatFilename($origFilename);
            $this->assertTrue(
                substr_count($newFilename, '.') <= 1,
                "Original filename: [{$origFilename}], new filename: [{$newFilename}]"
            );
        }
    }


    function testFormatFilenameShouldContainOnlyPlainCharacters() {
        foreach ($this->_bogusFilenames as $origFilename) {
            $newFilename = Garp_File::formatFilename($origFilename);
            $this->assertTrue(
                $this->_containsOnlyPlainCharacters($newFilename),
                "Original filename: [{$origFilename}], new filename: [{$newFilename}]"
            );
        }
    }


    function testGetCumulativeFilenameShouldReturnNonEmpty() {
        foreach ($this->_bogusFilenames as $origFilename) {
            $newFilename = Garp_File::getCumulativeFilename($origFilename);
            $this->assertTrue(
                !empty($newFilename),
                "Original filename: [{$origFilename}], new filename: [{$newFilename}]"
            );
        }
    }


    function testGetCumulativeFilenameShouldContainNoneOrASingleDot() {
        foreach ($this->_bogusFilenames as $origFilename) {
            $newFilename = Garp_File::getCumulativeFilename($origFilename);
            $this->assertTrue(
                substr_count($newFilename, '.') <= 1,
                "Original filename: [{$origFilename}], new filename: [{$newFilename}]"
            );
        }
    }


    function testGetCumulativeFilenameShouldContainOnlyPlainCharacters() {
        foreach ($this->_bogusFilenames as $origFilename) {
            $newFilename = Garp_File::getCumulativeFilename($origFilename);
            $this->assertTrue(
                $this->_containsOnlyPlainCharacters($newFilename),
                "Original filename: [{$origFilename}], new filename: [{$newFilename}]"
            );
        }
    }

    public function testReadOnlyCdnShouldProhibitStorage() {
        $this->_helper->injectConfigValues(
            array(
            'cdn' => array('readonly' => true))
        );

        $exception = null;
        try {
            $filename = 'wefoiejwoiejfeiowjfeowijfoewijf.txt';
            $file = new Garp_File(Garp_File::TYPE_DOCUMENTS);
            $file->clearContext();
            $file->store($filename, 'lorem ipsum');
            // add a remove() just in case the test fails and the store() succeeds
            $file->remove($filename);
        } catch (Exception $e) {
            $exception = $e->getMessage();
        }
        $this->assertEquals(Garp_File::EXCEPTION_CDN_READONLY, $exception);
    }

    public function testReadOnlyCdnShouldProhibitRemoval() {
        $this->_helper->injectConfigValues(
            array(
            'cdn' => array('readonly' => true))
        );

        $exception = null;
        try {
            $filename = 'wefoiejwoiejfeiowjfeowijfoewijf.txt';
            $file = new Garp_File(Garp_File::TYPE_DOCUMENTS);
            $file->clearContext();
            $file->remove($filename);
        } catch (Exception $e) {
            $exception = $e->getMessage();
        }
        $this->assertEquals(Garp_File::EXCEPTION_CDN_READONLY, $exception);
    }
    /**
     * Checks whether the argument provided contains only word characters
     * (a to z, A to Z, 0 to 9 or underscores), dashes and dots.
     * This excludes characters with accents.
     *
     * @param String $filename The filename to be checked
     * @return Boolean Whether the provided argument contains only plain characters.
     */
    protected function _containsOnlyPlainCharacters($filename) {
        return preg_match('/[^A-Za-z0-9_\.-]/', $filename) === 0;
    }

    public function setUp(): void {
        parent::setUp();
        $this->_helper->injectConfigValues(
            array(
            'cdn' => array(
                'type' => 'local',
                'extensions' => 'jpg,jpeg,gif,png,zip,pdf,xls,xlsx,csv,json',
                'path' => array(
                    'upload' => array(
                        'image' => GARP_APPLICATION_PATH . '/../tests/tmp',
                        'document' => GARP_APPLICATION_PATH . '/../tests/tmp'
                    ),
                    'static' => array(
                        'image' => GARP_APPLICATION_PATH . '/../tests/tmp',
                        'document' => GARP_APPLICATION_PATH . '/../tests/tmp'
                    )
                )
            )
            )
        );
    }
}
