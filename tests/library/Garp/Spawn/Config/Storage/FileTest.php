<?php
/**
 * This class tests Garp_Spawn_Model_Base.
 *
 * @package Tests
 * @author  David Spreekmeester <david@grrr.nl>
 * @group   Spawn
 */
// class Garp_Spawn_Config_Storage_FileTest extends Garp_Test_PHPUnit_TestCase {
//  protected $_mocks = array(
//      'directory' => null,
//      'extension' => 'json',
//      'modelName' => 'Bogus'
//  );


//  public function setUp() {
//      $this->_mocks['directory'] = APPLICATION_PATH .
//          "/../garp/tests/mocks/application/modules/default/models/config/";
//  }


//  function testMockConfigShouldContainBytes() {
//      $storage = new Garp_Spawn_Config_Storage_File(
//          $this->_mocks['directory'],
//          $this->_mocks['extension']
//      );
//      $rawConfig = $storage->load($this->_mocks['modelName']);

//      $this->assertGreaterThan(0, strlen($rawConfig));
//  }


//  function testShouldBeAbleToRetrieveObjectList() {
//      $storage = new Garp_Spawn_Config_Storage_File(
//          $this->_mocks['directory'],
//          $this->_mocks['extension']
//      );
//      $filenames = $storage->listObjectIds();

//      $this->assertGreaterThan(1, count($filenames));
//  }
// }
