<?php
/**
 * This class tests Garp_Spawn_Model_Set.
 *
 * @package Tests
 * @author  David Spreekmeester <david@grrr.nl>
 * @group   Spawn
 */
class Garp_Spawn_Model_SetTest extends Garp_Test_PHPUnit_TestCase {
    protected $_mocks = array(
        'directory' => null,
        'extension' => 'json'
    );

    /**
     * Garp_Spawn_Model_Set $_modelSet
     */
    protected $_modelSet;


    public function setUp(): void {
        $this->_mocks['directory'] = GARP_APPLICATION_PATH . '/../tests/model-config/';
        $this->_modelSet = $this->_constructMockModelSet();
    }


    public function testModelSetShouldContainModels() {
        $this->assertGreaterThan(0, count($this->_modelSet));
    }


    protected function _constructMockModelSet() {
        return Garp_Spawn_Model_Set::getInstance(
            new Garp_Spawn_Config_Model_Set(
                new Garp_Spawn_Config_Storage_File(
                    $this->_mocks['directory'],
                    $this->_mocks['extension']
                ),
                new Garp_Spawn_Config_Format_Json
            )
        );
    }
}
