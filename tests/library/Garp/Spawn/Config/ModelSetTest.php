<?php
/**
 * This class tests Garp_Spawn_Model_Base.
 *
 * @package Tests
 * @author  David Spreekmeester <david@grrr.nl>
 * @group   Spawn
 */
// class Garp_Spawn_Config_ModelSetTest extends Garp_Test_PHPUnit_TestCase {
//  protected $_mocks = array(
//      'directory' => null,
//      'extension' => 'json'
//  );


//  public function setUp() {
//      $this->_mocks['directory'] = APPLICATION_PATH .
//          "/../garp/tests/mocks/application/modules/default/models/config/";
//  }


//  function testModelSetShouldContainMoreThanOneModel() {
//      $modelSetConfig = $this->_constructModelSetConfig();

//      $this->assertGreaterThan(1, count($modelSetConfig));
//  }


//  function testFirstModelInSetShouldContainLabelProperty() {
//      $modelSetConfig = $this->_constructModelSetConfig();
//      $firstKeyInSet = key($modelSetConfig);

//      $this->assertArrayHasKey('label', (array)$modelSetConfig[$firstKeyInSet]);
//      $this->assertGreaterThan(0, strlen($modelSetConfig[$firstKeyInSet]['label']));
//  }


//  protected function _constructModelSetConfig() {
//      return new Garp_Spawn_Config_ModelSet(
//          new Garp_Spawn_Config_Storage_File(
//              $this->_mocks['directory'],
//              $this->_mocks['extension']
//          ),
//          new Garp_Spawn_Config_Format_Json
//      );
//  }
// }
