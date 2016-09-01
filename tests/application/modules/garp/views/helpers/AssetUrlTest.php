<?php
/**
 * @group AssetUrlHelper
 * @author Harmen Janssen <harmen@grrr.nl>
 */
class Garp_View_Helper_AssetUrlTest extends Garp_Test_PHPUnit_TestCase {

    public function testShouldBeChainable() {
        $this->assertTrue($this->_getHelper() === $this->_getHelper()->assetUrl());
    }

    public function testShouldRenderLocalAssetUrl() {
        $this->_helper->injectConfigValues(
            array(
                'cdn' => array(
                    'type' => 'local',
                    'css' => array('location' => 'local'),
                    'domain' => 'bananas.com',
                    'ssl' => false
                ),
            )
        );
        $this->assertEquals(
            'http://bananas.com/css/base.css?' . new Garp_Semver,
            $this->_getHelper()->assetUrl('/css/base.css')
        );
    }

    public function testShouldRenderS3AssetUrl() {
        $this->_helper->injectConfigValues(
            array(
            'cdn' => array(
                'type' => 's3',
                'domain' => 'static.loc.melkweg.nl.s3-website-us-east-1.amazonaws.com',
                'css' => array(
                    'location' => 's3'
                ),
            ),
            )
        );
        $removeSemver = $this->_createTmpSemver();
        $this->assertEquals(
            'http://static.loc.melkweg.nl.s3-website-us-east-1.amazonaws.com/css/base.css?'
            . new Garp_Semver,
            $this->_getHelper()->assetUrl('/css/base.css')
        );

        if ($removeSemver) {
            $this->_removeTmpSemver();
        }
    }

    public function testShouldRenderLocalVersionedAssetUrl() {
        $this->_helper->injectConfigValues(
            array(
            'cdn' => array(
                'type' => 'local',
                'css' => array('location' => 'local'),
            ),
            'assets' => array(
                'css' => array(
                    'root' => '/css/build/prod',
                    'build' => '' // empty build config value to check backwards comptability
                )
            )
            )
        );
        $removeSemver = $this->_createTmpSemver();
        $this->assertEquals(
            (string)$this->_getHelper()->assetUrl('main.css'),
            'http://static.loc.melkweg.nl.s3-website-us-east-1.amazonaws.com/css/build/prod/' .
                new Garp_Semver . '/main.css'
        );

        if ($removeSemver) {
            $this->_removeTmpSemver();
        }
    }

    public function testShouldRenderLocalVersionedAssetUrlTheNewWay() {
        $this->_helper->injectConfigValues(
            array(
            'cdn' => array(
                'type' => 'local',
                'css' => array('location' => 'local'),
            ),
            'assets' => array(
                'js' => array(
                    'build' => '/js/build/prod',
                    'root' => '/js/src'
                )
            )
            )
        );
        $removeSemver = $this->_createTmpSemver();
        $this->assertEquals(
            (string)$this->_getHelper()->assetUrl('main.js'),
            'http://static.loc.melkweg.nl.s3-website-us-east-1.amazonaws.com/js/build/prod/' .
                new Garp_Semver . '/main.js'
        );

        if ($removeSemver) {
            $this->_removeTmpSemver();
        }
    }

    public function testShouldFigureOutAssetRoot() {
        $this->_helper->injectConfigValues(
            array(
            'cdn' => array(
                'type' => 's3',
                'domain' => 'mystuff.com',
                'js' => array('location' => 's3'),
            ),
            'assets' => array(
                'js' => array('build' => 'foo/bar/lorem/ipsum')
            )
            )
        );
        $removeSemver = $this->_createTmpSemver();

        $this->assertEquals(
            'foo/bar/lorem/ipsum/' . new Garp_Semver . '/main.js',
            (string)$this->_getHelper()->assetUrl()->getVersionedBuildPath('main.js')
        );

        if ($removeSemver) {
            $this->_removeTmpSemver();
        }
    }

    public function testShouldRenderS3VersionedAssetUrl() {
        $this->_helper->injectConfigValues(
            array(
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
            )
        );
        $removeSemver = $this->_createTmpSemver();
        $expectedUrl = 'http://static.sesamestreet.co.uk/css/build/prod/' .
            new Garp_Semver . '/main.css';
        $this->assertEquals(
            $expectedUrl,
            (string)$this->_getHelper()->assetUrl('main.css')
        );

        if ($removeSemver) {
            $this->_removeTmpSemver();
        }
    }

    public function testShouldReturnBasenameIfAssetRootIsUnknown() {
        $this->_helper->injectConfigValues(
            array(
                'cdn' => array(
                    'type' => 'local',
                    'domain' => 'myprettythings.com'
                )
            )
        );
        $this->assertEquals(
            'http://myprettythings.com/foo.pdf',
            (string)$this->_getHelper()->assetUrl('foo.pdf')
        );
    }

    public function testShouldRenderInHttpWhenSslIsTrue() {
        $this->_helper->injectConfigValues(
            array(
            'cdn' => array(
                'type' => 's3',
                'ssl' => true,
                's3' => array(
                    'region' => 'eu-west',
                    'bucket' => 'doodoobucket'
                ),
                'css' => array('location' => 's3'),
                'domain' => 'my-custom-cdn.com'
            ),
            'assets' => array(
                'css' => array(
                    'location' => 's3'
                )
            )
            )
        );
        $removeSemver = $this->_createTmpSemver();
        $this->assertEquals(
            $this->_getHelper()->assetUrl('/main.css'),
            'https://my-custom-cdn.com/main.css?' . new Garp_Semver
        );

        if ($removeSemver) {
            $this->_removeTmpSemver();
        }
    }

    public function testShouldRenderProperS3UrlForHttps() {
        $this->_helper->injectConfigValues(
            array(
            'cdn' => array(
                'type' => 's3',
                'ssl' => true,
                's3' => array(
                    'region' => 'eu-west',
                    'bucket' => 'doodoobucket'
                ),
                'css' => array('location' => 's3'),
                // Note: default S3 Amazonaws scheme is used when domain is not set
                'domain' => null
            ),
            'assets' => array(
                'css' => array(
                    'location' => 's3'
                )
            )
            )
        );
        $removeSemver = $this->_createTmpSemver();
        $this->assertEquals(
            $this->_getHelper()->assetUrl('/main.css'),
            'https://s3-eu-west.amazonaws.com/doodoobucket/main.css?' . new Garp_Semver
        );

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
            return file_put_contents(
                $this->_getSemverPath(),
                "---
                :major: 34
                :minor: 9
                :patch: 10
                :special: ''"
            );
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
        $this->_helper->injectConfigValues(
            array(
            'cdn' => array(
                'ssl' => false
            ),
            'resources' => array(
                'view' => array(
                    'doctype' => 'html5'
                )
            )
            )
        );
    }

    public function tearDown() {
        parent::tearDown();
        $this->_getView()->clearVars();
    }

}
