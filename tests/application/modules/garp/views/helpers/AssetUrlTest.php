<?php
/**
 * @group Helpers
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
		$this->assertEquals('/css/base.css', $this->_getHelper()->assetUrl('/css/base.css'));
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
		$this->assertEquals(
			'http://static.loc.melkweg.nl.s3-website-us-east-1.amazonaws.com/css/base.css',
			$this->_getHelper()->assetUrl('/css/base.css'));
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
		$shouldRemoveSemver = false;
		if (!file_exists(APPLICATION_PATH . '/../.semver')) {
			$shouldRemoveSemver = true;
			file_put_contents(APPLICATION_PATH . '/../.semver',
				"---
				:major: 0
				:minor: 9
				:patch: 10
				:special: ''");
		}
		$this->assertEquals($this->_getHelper()->assetUrl('main.css'),
			'/css/build/prod/' . new Garp_Semver . '/main.css');

		if ($shouldRemoveSemver) {
			unlink(APPLICATION_PATH . '/../.semver');
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
		$shouldRemoveSemver = false;
		if (!file_exists(APPLICATION_PATH . '/../.semver')) {
			$shouldRemoveSemver = true;
			file_put_contents(APPLICATION_PATH . '/../.semver',
				"---
				:major: 0
				:minor: 9
				:patch: 10
				:special: ''");
		}

		$this->assertEquals(
			$this->_getHelper()->assetUrl()->getVersionedBuildPath('main.js'),
			'foo/bar/lorem/ipsum/' . new Garp_Semver . '/main.js'
		);

		if ($shouldRemoveSemver) {
			unlink(APPLICATION_PATH . '/../.semver');
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
		$shouldRemoveSemver = false;
		if (!file_exists(APPLICATION_PATH . '/../.semver')) {
			$shouldRemoveSemver = true;
			file_put_contents(APPLICATION_PATH . '/../.semver',
				"---
				:major: 34
				:minor: 129
				:patch: 10
				:special: ''");
		}
		$this->assertEquals($this->_getHelper()->assetUrl('main.css'),
			'http://static.sesamestreet.co.uk/css/build/prod/' . new Garp_Semver . '/main.css');

		if ($shouldRemoveSemver) {
			unlink(APPLICATION_PATH . '/../.semver');
		}
	}

	public function testShouldReturnBasenameIfAssetRootIsUnknown() {
		$this->_helper->injectConfigValues(array(
			'cdn' => array('type' => 'local')
		));
		$this->assertEquals('/foo.pdf', $this->_getHelper()->assetUrl('foo.pdf'));
	}

	protected function _getHelper() {
		return $this->_getView()->getHelper('assetUrl');
	}

	protected function _getView() {
		return Zend_Registry::get('application')->getBootstrap()->getResource('View');
	}

	public function tearDown() {
		parent::tearDown();
		$this->_getView()->clearVars();
	}

}
