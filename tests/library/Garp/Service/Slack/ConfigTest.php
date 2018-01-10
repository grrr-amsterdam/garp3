<?php
/**
 * This class tests Garp_Service_Slack_Config
 *
 * @package Tests
 * @author  David Spreekmeester <david@grrr.nl>
 * @group   Slack
 */
class Garp_Service_Slack_ConfigTest extends Garp_Test_PHPUnit_TestCase {

    /**
     * @var Garp_Service_Slack_Config $_config
     */
    protected $_config;

    /**
     * @return Garp_Service_Slack_Config
     */
    public function getConfig() {
        return $this->_config;
    }

    /**
     * @param Garp_Service_Slack_Config $config
     */
    public function setConfig($config) {
        $this->_config = $config;
        return $this;
    }


    public function setUp() {
        $mockConfig = array(
            'token' => 'GLKJKJHF234/234AKDJH/k234kjh324afa',
            'channel' => '#mychannel',
            'icon_emoji' => ':my_emoji:',
            'username' => 'myname'
        );

        $this->setConfig(new Garp_Service_Slack_Config($mockConfig));
    }


    public function testShouldHaveToken() {
        $config     = $this->getConfig();
        $token      = $config->getToken();

        $this->assertTrue(
            !empty($token),
            'Does Slack config have a token?'
        );
    }

    public function testShouldHaveTokenInParamsList() {
        $params     = $this->getConfig()->getParams();
        $token      = $params['token'];

        $this->assertTrue(
            !empty($token),
            'Does Slack config have a token in the list of params?'
        );
    }

    public function testOverridingParamShouldHaveEffectInParamsList() {
        $overrides  = array('token' => 'mylittlepony');
        $params     = $this->getConfig()->getParams($overrides);
        $token      = $params['token'];

        $this->assertEquals(
            'mylittlepony',
            $token,
            'Does Slack config have the correct overriding token?'
        );
    }
}
