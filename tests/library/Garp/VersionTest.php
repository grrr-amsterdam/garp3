<?php
/**
 * @package Tests
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_VersionTest extends Garp_Test_PHPUnit_TestCase {

    const MOCK_VERSION = 'v1.50.3-34-g684b4d79';

    public function testShouldReturnCorrectVersion() {
        $version = new Garp_Version($this->_getVersionLocation());
        $this->assertEquals(self::MOCK_VERSION, $version->getVersion());
        $this->assertEquals(self::MOCK_VERSION, (string)$version, '__toString() returns the version');
    }

    public function testShouldCacheVersionInMemory() {
        $version = new Garp_Version($this->_getVersionLocation());
        $this->assertEquals(self::MOCK_VERSION, $version->getVersion());
        file_put_contents(
            $this->_getVersionLocation(),
            'v1.0.23-g4390291'
        );
        $this->assertEquals(self::MOCK_VERSION, $version->getVersion(), 'The Version is cached in memory');

        Garp_Version::bustCache();
        $this->assertEquals('v1.0.23-g4390291', $version->getVersion(), 'bustCache() clears the cache');
    }

    public function setUp() {
        parent::setUp();
        file_put_contents($this->_getVersionLocation(), self::MOCK_VERSION);
        Garp_Version::bustCache();
    }

    public function tearDown() {
        unlink($this->_getVersionLocation());
    }

    protected function _getVersionLocation() {
        return GARP_APPLICATION_PATH . '/../tests/tmp/VERSION';
    }

}
