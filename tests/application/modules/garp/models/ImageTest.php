<?php
/**
 * @group Models
 */
class G_Model_ImageTest extends Garp_Test_PHPUnit_TestCase {

	protected $_mockImageUrl = 'https://www.google.com/images/logo.png';
	protected $_mockFilename = 'logo-mock.jpg';
	protected $_mockImageUrlWithQueryParams = 'https://www.google.com/images/logo.png?seg32=seg2&oh=93403634cddefe853629b06d2955bf7f&oe=54EAA1FD&__gda__=1425078319_70f87af9e7fd98a199b563e9c0944911';
	// The following mock string does not have query params on purpose, see test.
	// Also, it corresponds with the basename of $_mockImageUrlWithQueryParams
	protected $_mockFilenameWithQueryParams = 'logo.png';
	protected $_imageModel;

	public function testShouldCreateImageFromUrl() {
		$this->_getImageModel()->insertFromUrl($this->_mockImageUrl, $this->_mockFilename);
		$this->assertTrue(file_exists(GARP_APPLICATION_PATH . '/../tests/tmp/' .
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
		$this->assertTrue(file_exists(GARP_APPLICATION_PATH . '/../tests/tmp/' .
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
						'image' => GARP_APPLICATION_PATH . '/../tests/tmp'
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
