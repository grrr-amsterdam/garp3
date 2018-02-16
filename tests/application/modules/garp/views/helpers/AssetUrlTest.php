<?php
/**
 * @group AssetUrlHelper
 * @author Harmen Janssen <harmen@grrr.nl>
 */
class Garp_View_Helper_AssetUrlTest extends Garp_Test_PHPUnit_TestCase {

    /**
     * @test
     */
    public function should_be_chainable() {
        $this->assertTrue($this->_getHelper() === $this->_getHelper()->assetUrl());
    }

    /**
     * @test
     */
    public function should_use_baseurl_configuration_to_render_asseturl() {
        $this->_helper->injectConfigValues(
            array(
                'cdn' => array(
                    'baseUrl' => 'http://grrr-cdn.com'
                )
            )
        );
        $this->assertEquals(
            'http://grrr-cdn.com/foo.pdf',
            (string)$this->_getHelper()->assetUrl('foo.pdf')
        );

        // baseUrl can be anything, and includes protocol, domain, path, port, whatever
        $this->_helper->injectConfigValues(
            array(
                'cdn' => array(
                    'baseUrl' => 'ftp://stuff.grrr.nl:8888',
                    'css' => array('location' => 's3')
                )
            )
        );
        $this->assertEquals(
            'ftp://stuff.grrr.nl:8888/css/main.css?' . new Garp_Semver,
            (string)$this->_getHelper()->assetUrl('/css/main.css')
        );
    }

    /**
     * @test
     */
    public function should_return_baseurl_when_file_is_empty() {
        $baseUrl =  'httos://s3-eu-west-1.amazonaws.com/bucket';
        $this->_helper->injectConfigValues(
            array(
                'cdn' => array(
                    'baseUrl' => $baseUrl
                )
            )
        );
        $this->assertEquals(
            $baseUrl,
            (string)$this->_getHelper()->assetUrl('')
        );
    }

    /**
     * @test
     */
    public function should_render_local_asset_url_to_override_baseurl() {
        $this->_helper->injectConfigValues(
            array(
                'cdn' => array(
                    'type' => 's3',
                    's3' => array(
                        'region' => 'eu-west',
                        'bucket' => 'my-bucket'
                    ),
                    'css' => array('location' => 'local'),
                    'baseUrl' => 'http://s3-eu-west.amazonaws.com/my-bucket/',
                    'ssl' => false
                ),
            )
        );
        // Known exception: css has been overwritten to be local
        $this->assertEquals(
            (string)$this->_getHelper()->assetUrl('/css/base.css'),
            '/css/base.css?' . new Garp_Semver
        );
        // Regular file, should get cdn.baseUrl
        $this->assertEquals(
            (string)$this->_getHelper()->assetUrl('/uploads/foo.pdf'),
            'http://s3-eu-west.amazonaws.com/my-bucket/uploads/foo.pdf?' . new Garp_Semver
        );
    }

    /**
     * @test
     */
    public function should_render_s3_asset_url() {
        $this->_helper->injectConfigValues(
            array(
            'cdn' => array(
                'type' => 's3',
                'baseUrl' => 'http://static.loc.melkweg.nl.s3-website-us-east-1.amazonaws.com',
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

    /**
     * @test
     */
    public function should_render_local_versioned_asset_url() {
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
            '/css/build/prod/' .
                new Garp_Semver . '/main.css'
        );

        if ($removeSemver) {
            $this->_removeTmpSemver();
        }
    }

    /**
     * @test
     */
    public function should_render_local_versioned_asset_url_the_new_way() {
        $this->_helper->injectConfigValues(
            array(
            'cdn' => array(
                'type' => 'local',
                'js' => array(
                    'location' => 'local'
                ),
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
            '/js/build/prod/' . new Garp_Semver . '/main.js'
        );

        if ($removeSemver) {
            $this->_removeTmpSemver();
        }
    }

    /**
     * @test
     */
    public function should_figure_out_asset_root() {
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

    /**
     * @test
     */
    public function should_render_versioned_asset_url() {
        $this->_helper->injectConfigValues(
            array(
            'cdn' => array(
                'type' => 's3',
                'baseUrl' => 'http://static.sesamestreet.co.uk',
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
        parent::setUp();
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
