<?php
/**
 * @group Git
 */
class Garp_Git_HashTest extends Garp_Test_PHPUnit_TestCase {
    protected $_mockRepoHeadsPath =
        'tests/files/';
    protected $_mockGitHash = 'MOCK GIT HASH';

    // @todo Make branch dynamic
    protected $_gitBranch = 'master';

    public function testCapStrategyShouldReturnHash() {
        //$strategy = new Garp_Git_Hash_Strategy_Cap3(
            //$this->_getMockGitFilePath()
        //);

        //try {
            //$hash = new Garp_Git_Hash($strategy);
        //} catch (Exception $e) {}

        //$this->_placeCapFile();
//exit($hash->getHash());
        //$this->assertEquals(
            //$this->_mockGitHash,
            //$hash->getHash()
        //);
        //$this->_removeCapFile();
    }

    public function setUp() {
        parent::setUp();
        //file_put_contents($this->_getSemverLocation(),
            //"---
            //:major: 2
            //:minor: 4
            //:patch: 29
            //:special: ''");
        //file_put_contents($this->_getSpecialSemverLocation(),
            //"---
            //:major: 0
            //:minor: 7
            //:patch: 7
            //:special: 'alpha'"); 
    }

    public function tearDown() {
    }

    protected function _getMockGitFilePath() {
        return GARP_APPLICATION_PATH . '/../'
            . $this->_mockRepoHeadsPath
            . $this->_gitBranch;
    }

    protected function _placeCapFile() {
        file_put_contents(
            $this->_getMockGitFilePath(),
            $this->_mockGitHash
        );
    }

    protected function _removeCapFile() {
        unlink($this->_getMockGitFilePath());
    }
}
