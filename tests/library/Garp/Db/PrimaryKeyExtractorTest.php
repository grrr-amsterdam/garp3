<?php
/**
 * Tests Garp_Db_PrimaryKeyExtractorTest
 * @group Db
 */
class Garp_Db_PrimaryKeyExtractorTest extends Garp_Test_PHPUnit_TestCase {
	public function setUp() {
		// Create bogus models
		$this->_singularPkModel = new Zend_Db_Table(array(
			'name' => 'singular_pk',
			'primary' => 'id'
		));
	}

	public function testingSingularPkModel() {
		$whereClauses = array(
			'id = 1'      => array('id' => 1),
			'id = 100'    => array('id' => 100),
			'`id` = 1'    => array('id' => 1),
			'id = "1"'    => array('id' => 1),
			'id = \'1\''  => array('id' => 1),
			'id = 1 AND name LIKE "%something%"' => array('id' => 1),
			'`id` = 100 AND name = "harmen"' => array('id' => 100)
		);
		foreach ($whereClauses as $whereClause => $result) {
			$pkExtractor = new Garp_Db_PrimaryKeyExtractor($this->_singularPkModel, $whereClause);
			$this->assertEquals($pkExtractor->extract(), $result);
		}
	}
}
