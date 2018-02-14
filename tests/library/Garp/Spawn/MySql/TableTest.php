<?php
/**
 * This class tests Garp_Spawn_Db_Table.
 *
 * @package Tests
 * @author  David Spreekmeester <david@grrr.nl>
 * @group   Spawn
 */
class Garp_Spawn_Db_TableTest extends Garp_Test_PHPUnit_TestCase {
    protected $_mocks = array(
        'directory' => null,
        'modelName' => 'Bogus',
        'extension' => 'json',
        'sql'       => null
    );


    public function setUp() {
        $this->_mocks['directory'] = GARP_APPLICATION_PATH . "/../tests/model-config/";
        $this->_mocks['sql'] = file_get_contents(
            GARP_APPLICATION_PATH . '/../tests/files/bogus.sql'
        );
    }

    function testTableShouldContainColumns() {
        $table = new Garp_Spawn_Db_Table_Base(
            $this->_mocks['sql'],
            $this->_constructMockModel()
        );
        $this->assertEquals(count($table->columns), 10);
    }

    function testTableShouldHaveValidName() {
        $table = new Garp_Spawn_Db_Table_Base(
            $this->_mocks['sql'],
            $this->_constructMockModel()
        );
        $this->assertEquals($table->name, 'bogus');
    }

    protected function _constructMockModel() {
        $modelConfig = new Garp_Spawn_Config_Model_Base(
            $this->_mocks['modelName'],
            new Garp_Spawn_Config_Storage_File(
                $this->_mocks['directory'],
                $this->_mocks['extension']
            ),
            new Garp_Spawn_Config_Format_Json
        );

        return new Garp_Spawn_Model_Base($modelConfig);
    }

    //  TODO testen van $table->keys
}
