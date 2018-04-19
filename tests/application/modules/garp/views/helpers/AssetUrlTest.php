<?php
/**
 * @package Tests
 * @author  Harmen Janssen <harmen@grrr.nl>
 * @group AssetUrlHelper
 */
class Garp_View_Helper_AssetUrlTest extends Garp_Test_PHPUnit_TestCase {

    const MOCK_VERSION = 'v1.0.23-g4390291';

    protected $_usedVersion;

    /**
     * Wether to remove VERSION file on tearDown
     *
     * @var bool
     */
    protected $_clearVersion = false;

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
            'http://grrr-cdn.com/foo.pdf?' . $this->_usedVersion,
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
            'ftp://stuff.grrr.nl:8888/css/main.css?' . $this->_usedVersion,
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
            '/css/base.css?' . $this->_usedVersion,
            (string)$this->_getHelper()->assetUrl('/css/base.css')
        );
        // Regular file, should get cdn.baseUrl
        $this->assertEquals(
            'http://s3-eu-west.amazonaws.com/my-bucket/uploads/foo.pdf?' . $this->_usedVersion,
            (string)$this->_getHelper()->assetUrl('/uploads/foo.pdf')
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
        $this->assertEquals(
            'http://static.loc.melkweg.nl.s3-website-us-east-1.amazonaws.com/css/base.css?' . $this->_usedVersion,
            $this->_getHelper()->assetUrl('/css/base.css')
        );
    }

    /**
     * @test
     */
    public function should_use_microtime_in_the_absence_of_version() {
        $versionSrc = $this->_getVersionPath();
        $tmpTarget = dirname($versionSrc) . '/VERSION_TMP';
        // Move VERSION file to ensure its absence.
        rename($versionSrc, $tmpTarget);
        $this->_helper->injectConfigValues(
            array(
                'cdn' => array(
                    'css' => array('location' => 'local')
                )
            )
        );
        $cssUrl = $this->_getHelper()->assetUrl('/css/base.css');
        $urlBits = parse_url($cssUrl);
        $this->assertArrayNotHasKey('host', $urlBits);
        $this->assertArrayHasKey('path', $urlBits);
        $this->assertArrayHasKey('query', $urlBits);
        $this->assertTrue(is_numeric($urlBits['query']));

        // Reset VERSION file.
        rename($tmpTarget, $versionSrc);
    }

    protected function _getHelper() {
        return $this->_getView()->getHelper('assetUrl');
    }

    protected function _getView() {
        return Zend_Registry::get('application')->getBootstrap()->getResource('View');
    }

    protected function _getVersionPath() {
        return APPLICATION_PATH . '/../VERSION';
    }

    protected function _removeVersion() {
        return unlink($this->_getVersionPath());
    }

    protected function _doesVersionExist() {
        return file_exists($this->_getVersionPath());
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

        if (!$this->_doesVersionExist()) {
            $this->_usedVersion = static::MOCK_VERSION;
            $this->_clearVersion = true;
            return file_put_contents(
                $this->_getVersionPath(),
                static::MOCK_VERSION
            );
        } else {
            $this->_usedVersion = (new Garp_Version())->__toString();
        }
    }

    public function tearDown() {
        parent::tearDown();
        $this->_getView()->clearVars();
        if ($this->_clearVersion) {
            $this->_removeVersion();
        }
        Garp_Version::bustCache();
    }

}
