<?php
/**
 * @group File
 */
class Garp_File_Storage_S3_Test extends Garp_Test_PHPUnit_TestCase {
	protected $_storage;
	protected $_gzipTestFile = '19209ujr203r20rk409rk2093ir204r92r90.txt';

	public function testShouldGzipOutput() {
		if (!$this->_isS3Configured()) {
			$this->assertTrue(true, "S3 is not configured");
			return;
		}


		$testContent =  'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';
		$this->_storage->store($this->_gzipTestFile, $testContent, true);

		$contents = $this->_storage->fetch($this->_gzipTestFile);
		$this->assertTrue(strlen($contents) > 0);

		// Alas: the service deflates the contents so there's no real checking wether
		// the contents actually arrives gzipped. Still: it's useful to check wether the contents
		// actually deflate to the right string.
		$this->assertEquals($testContent, $contents);

	}

	public function testGetList() {
		if (!$this->_isS3Configured()) {
			$this->assertTrue(true, "S3 is not configured");
			return;
		}

		if (!($cdnConfig = $this->_findFirstS3Config())) {
			return;
		}

		$s3 		= new Garp_File_Storage_S3($cdnConfig, $cdnConfig->path->upload->image);
		$list 		= $s3->getList();

		$this->assertTrue((bool)count($list));
	}

	protected function _findFirstS3Config() {
		$envs = array('production', 'staging', 'integration', 'development');

		foreach ($envs as $env) {
			$ini = new Garp_Config_Ini('application/configs/application.ini', $env);
			if ($ini->cdn->type === 's3') {
				return $ini->cdn;
			}
		}
	}

	public function setUp() {
		$this->_helper->injectConfigValues(array(
			'cdn' => array(
				'gzip' => true,
				's3' => array(
				)
			)
		));

		if ($this->_isS3Configured()) {
			$this->_storage = new Garp_File_Storage_S3(Zend_Registry::get('config')->cdn, '/');
		}
	}

	public function tearDown() {
		if ($this->_storage) {
			$this->_storage->remove($this->_gzipTestFile);
		}
	}

	protected function _isS3Configured() {
		return isset(Zend_Registry::get('config')->cdn->s3->apikey);
	}
}
