<?php
/**
 * @group Models
 */
class G_Model_ImageTest extends Garp_Test_PHPUnit_TestCase {

	protected $_mockImageUrl = 'http://static.melkweg.nl/uploads/images/krs-one-web.jpg';
	protected $_mockFilename = 'krs-one.jpg';
	protected $_mockImageUrlWithQueryParams = 'https://fbcdn-profile-a.akamaihd.net/hprofile-ak-xpf1/v/t1.0-1/c24.24.297.297/s50x50/1013240_10201006260230884_1892541638_n.jpg?oh=93403634cddefe853629b06d2955bf7f&oe=54EAA1FD&__gda__=1425078319_70f87af9e7fd98a199b563e9c0944911';
	protected $_mockFilenameWithQueryParams = '1013240-10201006260230884-1892541638-n.jpg';
	protected $_imageModel;

	public function testShouldCreateImageFromUrl() {
		$this->_getImageModel()->insertFromUrl($this->_mockImageUrl, $this->_mockFilename);
		$this->assertTrue(file_exists(APPLICATION_PATH . '/../public/../garp/tests/tmp/' . 
			$this->_mockFilename));
	}

	public function testShouldCreateImageRecordFromUrl() {
		$imgId = $this->_getImageModel()->insertFromUrl($this->_mockImageUrl, $this->_mockFilename);
		$imgRow = $this->_getImageModel()->fetchRow(
			$this->_getImageModel()->select()->where('filename = ?', $this->_mockFilename));
		$this->assertFalse(is_null($imgRow));
		$this->assertEquals($imgId, $imgRow->id);
	}

	public function testShouldStripQueryParamsFromFilename() {
		$imgId = $this->_getImageModel()->insertFromUrl($this->_mockImageUrlWithQueryParams);
		$this->assertTrue(file_exists(APPLICATION_PATH . '/../public/../garp/tests/tmp/' . 
			$this->_mockFilenameWithQueryParams));
	}

	protected function _getImageModel() {
		if (!$this->_imageModel) {
			$this->_imageModel = new Model_Image();
			$this->_imageModel->unregisterObserver('Authorable');
			$this->_imageModel->unregisterObserver('ImageScalable');
		}
		return $this->_imageModel;
	}

	public function setUp() {
		$this->_helper->injectConfigValues(array(
			'cdn' => array(
				'type' => 'local',
				'path' => array(
					'upload' => array(
						'image' => '/../garp/tests/tmp' 
					)
				)
			) 
		));
	}

	public function tearDown() {
		$this->_getImageModel()->delete('id > 0');
		$paths = array(
			GARP_APPLICATION_PATH . '/../tests/tmp/' . $this->_mockFilename,
			GARP_APPLICATION_PATH . '/../tests/tmp/' . $this->_mockFilenameWithQueryParams
		);
		foreach ($paths as $path) {
			file_exists($path) && unlink($path);
		}
	}

}
