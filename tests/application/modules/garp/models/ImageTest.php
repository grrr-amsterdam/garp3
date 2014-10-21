<?php
/**
 * @group Models
 */
class G_Model_ImageTest extends Garp_Test_PHPUnit_TestCase {

	protected $_mockImageUrl = 'http://static.melkweg.nl/uploads/images/krs-one-web.jpg';
	protected $_mockFilename = 'krs-one.jpg';
	protected $_imageModel;

	public function testShouldCreateRecordFromUrl() {
		$this->_getImageModel()->insertFromUrl($this->_mockImageUrl, $this->_mockFilename);
		$this->assertTrue(file_exists(APPLICATION_PATH . '/../public/../garp/tests/tmp/' . 
			$this->_mockFilename));
	}

	protected function _getImageModel() {
		if (!$this->_imageModel) {
			$this->_imageModel = new Model_Image();
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
	}

}
