<?php
/**
 * @author David Spreekmeester | Grrr.nl
 * This class tests Garp_Spawn_MySql_Table.
 * @group Spawn
 */
class Garp_Spawn_MySql_TableTest extends PHPUnit_Framework_TestCase {
	protected $_mocks = array(
		'directory' => null,
		'modelName' => 'Bogus',
		'extension' => 'json',
		'sql'		=> null
	);


	public function setUp() {
		$this->_mocks['directory'] = APPLICATION_PATH . "/../garp/application/modules/mocks/models/config/";
		$this->_mocks['sql'] = file_get_contents($this->_mocks['directory'] . '../sql/bogus.sql');
	}

	function testTableShouldContainColumns() {
		$table = new Garp_Spawn_MySql_Table_Base($this->_mocks['sql'], $this->_constructMockModel());
		$this->assertEquals(count($table->columns), 10);
	}

	function testTableShouldHaveValidName() {
		$table = new Garp_Spawn_MySql_Table_Base($this->_mocks['sql'], $this->_constructMockModel());
		$this->assertEquals($table->name, 'bogus');
	}

	protected function _constructMockModel() {
		$modelConfig = new Garp_Spawn_Config_Model_Base(
			$this->_mocks['modelName'],
			new Garp_Spawn_Config_Storage_File($this->_mocks['directory'], $this->_mocks['extension']),
			new Garp_Spawn_Config_Format_Json
		);

		return new Garp_Spawn_Model_Base($modelConfig);
	}

	//	TODO testen van $table->keys
}
