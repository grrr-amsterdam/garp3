<?php
/**
 * @group Git
 */
class Garp_Git_Hash_Strategy_Cap3Test
    extends Garp_Test_PHPUnit_TestCase {

    protected $_mockRepoHeadsPath = 'tests/files/';
    protected $_mockGitHash = 'MOCK GIT HASH';

    // @todo Make branch dynamic
    protected $_gitBranch = 'master';

    public function testCapStrategyShouldReturnHash() {
        $path = $this->_getMockGitHeadsPath();
        $strategy = new Garp_Git_Hash_Strategy_Cap3($path);

        try {
            $hash = $strategy->getHash();
        } catch (Exception $e) {
            $hash = null;
        }

        $this->assertEquals(
            $this->_mockGitHash,
            $hash
        );
    }

    public function setUp() {
        parent::setUp();
        $this->_placeCapFile();
    }

    public function tearDown() {
        $this->_removeCapFile();
    }

    protected function _getMockGitHeadsPath() {
        return GARP_APPLICATION_PATH . '/../'
            . $this->_mockRepoHeadsPath;
    }

    protected function _placeCapFile() {
        file_put_contents(
            $this->_getMockGitHeadsPath()
            . $this->_gitBranch,
            $this->_mockGitHash
        );
    }

    protected function _removeCapFile() {
        unlink(
            $this->_getMockGitHeadsPath()
            . $this->_gitBranch
        );
    }
}
