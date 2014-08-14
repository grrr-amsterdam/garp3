<?php
/**
 * @group Semver
 */
class Garp_SemverTest extends Garp_Test_PHPUnit_TestCase {

	public function testShouldReturnCorrectSemver() {
		$semver = new Garp_Semver($this->_getSemverLocation());
		$this->assertEquals('v2.4.29', $semver->getVersion());
		$this->assertEquals('v2.4.29', (string)$semver);
	}

	public function testShouldReturnCorrectSpecialSemver() {
		$semver = new Garp_Semver($this->_getSpecialSemverLocation());
		$this->assertEquals('v0.7.7-alpha', $semver->getVersion());
	}

	public function setUp() {
		parent::setUp();
		file_put_contents($this->_getSemverLocation(),
			"---
			:major: 2
			:minor: 4
			:patch: 29
			:special: ''");
		file_put_contents($this->_getSpecialSemverLocation(),
			"---
			:major: 0
			:minor: 7
			:patch: 7
			:special: 'alpha'"); 
	}

	public function tearDown() {
		unlink($this->_getSemverLocation());
		unlink($this->_getSpecialSemverLocation());
	}

	protected function _getSemverLocation() {
		return GARP_APPLICATION_PATH . '/../tests/tmp/.semver';
	}

	protected function _getSpecialSemverLocation() {
		return GARP_APPLICATION_PATH . '/../tests/tmp/.special-semver';
	}
}
