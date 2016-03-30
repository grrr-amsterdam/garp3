<?php
/**
 * @group AssetUrlHelper
 */
class Garp_View_Helper_AssetUrlTest extends Garp_Test_PHPUnit_TestCase {

	public function testShouldBeChainable() {
		$this->assertTrue($this->_getHelper() === $this->_getHelper()->assetUrl());
	}

	public function testShouldRenderLocalAssetUrl() {
		$this->_helper->injectConfigValues(array(
			'cdn' => array(
				'type' => 'local',
				'css' => array('location' => 'local'),
			),
		));
		$this->assertEquals(
			'/css/base.css?' . new Garp_Semver,
			$this->_getHelper()->assetUrl('/css/base.css')
		);
	}

	public function testShouldRenderS3AssetUrl() {
		$this->_helper->injectConfigValues(array(
			'cdn' => array(
				'type' => 's3',
				'domain' => 'static.loc.melkweg.nl.s3-website-us-east-1.amazonaws.com',
				'css' => array(
					'location' => 's3'
				),
			),
		));
		$removeSemver = $this->_createTmpSemver();
		$this->assertEquals(
			'http://static.loc.melkweg.nl.s3-website-us-east-1.amazonaws.com/css/base.css?'
			. new Garp_Semver,
			$this->_getHelper()->assetUrl('/css/base.css'));

		if ($removeSemver) {
			$this->_removeTmpSemver();
		}
	}

	public function testShouldRenderLocalVersionedAssetUrl() {
		$this->_helper->injectConfigValues(array(
			'cdn' => array(
				'type' => 'local',
				'css' => array('location' => 'local'),
			),
			'assets' => array(
				'css' => array(
					'root' => '/css/build/prod'
				)
			)
		));
		$removeSemver = $this->_createTmpSemver();
		$this->assertEquals($this->_getHelper()->assetUrl('main.css'),
			'/css/build/prod/' . new Garp_Semver . '/main.css');

		if ($removeSemver) {
			$this->_removeTmpSemver();
		}
	}

	public function testShouldFigureOutAssetRoot() {
		$this->_helper->injectConfigValues(array(
			'cdn' => array(
				'type' => 's3',
				'domain' => 'mystuff.com',
				'js' => array('location' => 's3'),
			),
			'assets' => array(
				'js' => array('root' => 'foo/bar/lorem/ipsum')
			)
		));
		$removeSemver = $this->_createTmpSemver();

		$this->assertEquals(
			$this->_getHelper()->assetUrl()->getVersionedBuildPath('main.js'),
			'foo/bar/lorem/ipsum/' . new Garp_Semver . '/main.js'
		);

		if ($removeSemver) {
			$this->_removeTmpSemver();
		}
	}

	public function testShouldRenderS3VersionedAssetUrl() {
		$this->_helper->injectConfigValues(array(
			'cdn' => array(
				'type' => 's3',
				'domain' => 'static.sesamestreet.co.uk',
				'css' => array('location' => 's3'),
			),
			'assets' => array(
				'css' => array(
					'root' => '/css/build/prod'
				)
			)
		));
		$removeSemver = $this->_createTmpSemver();
		$expectedUrl = 'http://static.sesamestreet.co.uk/css/build/prod/' .
			new Garp_Semver . '/main.css';
		$this->assertEquals($expectedUrl, $this->_getHelper()->assetUrl('main.css'));

		if ($removeSemver) {
			$this->_removeTmpSemver();
		}
	}

	public function testShouldReturnBasenameIfAssetRootIsUnknown() {
		$this->_helper->injectConfigValues(array(
			'cdn' => array('type' => 'local')
		));
		$this->assertEquals('/foo.pdf', $this->_getHelper()->assetUrl('foo.pdf'));
	}

	public function testShouldRenderProperS3UrlForHttps() {
		$this->_helper->injectConfigValues(array(
			'cdn' => array(
				'type' => 's3',
				'ssl' => true,
				's3' => array(
					'region' => 'eu-west',
					'bucket' => 'doodoobucket'
				),
				'css' => array('location' => 's3'),
			),
			'assets' => array(
				'css' => array(
					'location' => 's3'
				)
			)
		));
		$removeSemver = $this->_createTmpSemver();
		$this->assertEquals($this->_getHelper()->assetUrl('/main.css'),
			'https://s3-eu-west.amazonaws.com/doodoobucket/main.css?' . new Garp_Semver);

		if ($removeSemver) {
			$this->_removeTmpSemver();
		}
	}

	protected function _getSemverPath() {
		return APPLICATION_PATH . '/../.semver';
	}

	protected function _doesSemverExist() {
		return file_exists($this->_getSemverPath());
	}

	protected function _createTmpSemver() {
		if (!$this->_doesSemverExist()) {
			return file_put_contents($this->_getSemverPath(),
				"---
				:major: 34
				:minor: 9
				:patch: 10
				:special: ''");
		}
	}

	protected function _removeTmpSemver() {
		if ($this->_doesSemverExist()) {
			unlink($this->_getSemverPath());
		}
	}

	protected function _getHelper() {
		return $this->_getView()->getHelper('assetUrl');
	}

	protected function _getView() {
		return Zend_Registry::get('application')->getBootstrap()->getResource('View');
	}

	public function setUp() {
		$this->_helper->injectConfigValues(array(
			'cdn' => array(
				'ssl' => false
			),
			'resources' => array(
				'view' => array(
					'doctype' => 'html5'
				)
			)
		));
	}

	public function tearDown() {
		parent::tearDown();
		$this->_getView()->clearVars();
	}

}
