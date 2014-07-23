<?php
/**
 * @group File
 */
class Garp_File_Storage_LocalTest extends Garp_Test_PHPUnit_TestCase {

	protected $_storage;
	protected $_gzipTestFile = '19209ujr203r20rk409rk2093ir204r92r90.txt';

	public function testShouldGzipOutput() {

		$testContent =  'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';
		$this->_storage->store($this->_gzipTestFile, $testContent, true);

		$contents = file_get_contents(GARP_APPLICATION_PATH . '/../tests/tmp/' . $this->_gzipTestFile);
		$this->assertTrue(strlen($contents) > 0);
		$this->assertNotEquals($testContent, $contents);
		$this->assertEquals($testContent, gzdecode($contents));

		// Check wether storage->fetch() returned deflated content
		$storedContents = $this->_storage->fetch($this->_gzipTestFile);
		$this->assertEquals($testContent, $storedContents);
	}

	public function setUp() {
		$this->_helper->injectConfigValues(array(
			'cdn' => array(
				'gzip' => true
			)
		));
		$this->_storage = new Garp_File_Storage_Local(Zend_Registry::get('config')->cdn, 'tmp');
		$this->_storage->setDocRoot(GARP_APPLICATION_PATH . '/../tests/');
	}

	public function tearDown() {
		$this->_storage->remove($this->_gzipTestFile);
	}

}
