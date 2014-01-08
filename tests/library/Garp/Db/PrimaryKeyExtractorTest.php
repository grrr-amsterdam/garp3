<?php
/**
 * Tests Garp_Db_PrimaryKeyExtractorTest
 * @group Db
 */
class Garp_Db_PrimaryKeyExtractorTest extends Garp_Test_PHPUnit_TestCase {
	protected $_singularPkModel;
	protected $_multiPkModel;

	public function testingSingularPkModelWithDigitPk() {
		$whereClauses = array(
			'name = "FRANK"' => array(),
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

	/**
 	 * In the past the following failed:
 	 * WHERE yesplan_id = "123456"
 	 * This returned: array('id' => "123456")
 	 * This test asserts this is fixed.
 	 */
	public function testingSingularPkModelWithSimilarColumn() {
		$pkExtractor = new Garp_Db_PrimaryKeyExtractor($this->_singularPkModel, 'yesplan_id = 199');
		$this->assertEquals($pkExtractor->extract(), array());
	}

	public function testingSingularPkModelWithStringPk() {
		$whereClauses = array(
			'id = \'Harmen\'' => array('id' => 'Harmen'),
			'id = "frits van der plof"' => array('id' => 'frits van der plof')
		);
		foreach ($whereClauses as $whereClause => $result) {
			$pkExtractor = new Garp_Db_PrimaryKeyExtractor($this->_singularPkModel, $whereClause);
			$this->assertEquals($pkExtractor->extract(), $result);
		}
	}

	public function testingMultiPkModelWithDigitPk() {
		$whereClauses = array(
			'name = "FRANK"' => array(),
			'id1 = 1' => array('id1' => 1),
			'((`id1` = 1))' => array('id1' => 1),
			'id1 = 100 AND id2 = 200' => array('id1' => 100, 'id2' => 200),
			'id1 = "1" AND `id2` = 300' => array('id1' => 1, 'id2' => 300),
			'`id1` = "100" OR `id2` = 500 OR UPPER(name) = "FRANK"' => array('id1' => 100, 'id2' => 500)
		);
		foreach ($whereClauses as $whereClause => $result) {
			$pkExtractor = new Garp_Db_PrimaryKeyExtractor($this->_multiPkModel, $whereClause);
			$this->assertEquals($pkExtractor->extract(), $result);
		}
	}

	public function testingMultiPkModelWithStringPk() {
		$whereClauses = array(
			'id1 = \'Harmen\'' => array('id1' => 'Harmen'),
			'id1 = "frits van der plof" AND id2 = "Harmen Janssen"' => array('id1' => 'frits van der plof', 'id2' => 'Harmen Janssen'),
			'id2 = "abc"' => array('id2' => 'abc'),
		);
		foreach ($whereClauses as $whereClause => $result) {
			$pkExtractor = new Garp_Db_PrimaryKeyExtractor($this->_multiPkModel, $whereClause);
			$this->assertEquals($pkExtractor->extract(), $result);
		}
	}

	public function setUp() {
		$dbAdapter = $this->getDatabaseAdapter();
		$dbAdapter->query('DROP TABLE IF EXISTS `_pkExtractorTestSingle`;');
		$dbAdapter->query('
		CREATE TABLE `_pkExtractorTestSingle`(
			`id` int UNSIGNED NOT NULL,
			PRIMARY KEY (`id`)
		) ENGINE=`InnoDB`;');
		$dbAdapter->query('DROP TABLE IF EXISTS `_pkExtractorTestMulti`;');
		$dbAdapter->query('
		CREATE TABLE `_pkExtractorTestMulti`(
			`id1` int UNSIGNED NOT NULL,
			`id2` int UNSIGNED NOT NULL,
			PRIMARY KEY (`id1`,`id2`)
		) ENGINE=`InnoDB`;');

		// Create bogus models
		$this->_singularPkModel = new Mocks_Model_PKExtractorSingle();
		$this->_multiPkModel = new Mocks_Model_PKExtractorMulti();
	}

	public function tearDown() {
		$dbAdapter = $this->getDatabaseAdapter();
		$dbAdapter->query('DROP TABLE `_pkExtractorTestSingle`;');
		$dbAdapter->query('DROP TABLE `_pkExtractorTestMulti`;');
	}
}
