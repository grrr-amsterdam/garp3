<?php
/**
 * Garp_Content_Distribution
 *
 * @package Tests
 * @author  David Spreekmeester <david@grrr.nl>
 * @group   CDN
 */
class Garp_Content_Cdn_Distributor_Test extends Garp_Test_PHPUnit_TestCase {
    const FILTER_STRING_MATCHING_MULTIPLE = 'css';
    const FILTER_STRING_MATCHING_ONE = 'css/images/garp.png';
    const FILTER_STRING_NOT_MATCHING = 'l3$#j@[hdv%@u2w2a9g08u.e3#d@c';


    public function test_No_Assets_Should_Be_Selected_If_No_Match() {
        $distributor = $this->_getDistributor();
        $assetList   = $distributor->select(self::FILTER_STRING_NOT_MATCHING, false);

        $this->assertSame(count($assetList), 0);
    }


    public function test_Multiple_Assets_Should_Be_Selected_If_Match() {
        $distributor = $this->_getDistributor();
        $assetList   = $distributor->select(self::FILTER_STRING_MATCHING_MULTIPLE, false);

        $this->assertTrue((bool)count($assetList));
    }


    public function test_One_Asset_Should_Be_Selected_If_Specific_Match() {
        $distributor = $this->_getDistributor();
        $assetList   = $distributor->select(self::FILTER_STRING_MATCHING_ONE, false);

        $this->assertSame(count($assetList), 1);
    }

    /**
     * @expectedException Garp_File_Exception
     */
    public function test_should_throw_when_given_readonly_config() {
        $distributor = $this->_getDistributor();
        $distributor->distribute(
            array('apikey' => 'abc', 'bucket' => 'bouquet', 'secret' => 'xxx', 'readonly' => '1'),
            new Garp_Content_Cdn_AssetList(GARP_APPLICATION_PATH . '/../public')
        );
    }

    protected function _getDistributor() {
        return new Garp_Content_Cdn_Distributor(GARP_APPLICATION_PATH . '/../public');
    }
}
