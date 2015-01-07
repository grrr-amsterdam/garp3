<?php
/**
 * @group Garp_Model_Db
 */
class Garp_Model_DbTest extends Garp_Test_PHPUnit_TestCase {

	public function testFilterColumnsShouldExtractAlienColumns() {
		$model = new Mocks_Model_GMDFooBar;
		$cols = array(
			'name' => 'Henk',
			'id' => 666,
			'author_id' => 19,
			'something' => 'Another thing',
			'haha' => 'hihi'
		);
		$expectedCols = array(
			'name' => 'Henk',
			'id' => 666,
			'author_id' => 19
		);
		// Try some extraneous columns
		$this->assertEquals($expectedCols, $model->filterColumns($cols));

		// Try empty array
		$this->assertEquals(array(), $model->filterColumns(array()));
	}

	public function setUp() {
		parent::setUp();
		$adapter = $this->getDatabaseAdapter();
		$adapter->query('DROP TABLE IF EXISTS `_gmd_foobar`;');
		$adapter->query('
		CREATE TABLE `_gmd_foobar` (
			`id` int UNSIGNED NOT NULL AUTO_INCREMENT,
			`name` varchar(20) NOT NULL,
			`author_id` int UNSIGNED,
			`modifier_id` int UNSIGNED,
			PRIMARY KEY (`id`)
		) ENGINE=`InnoDB`;
		');
	}

	public function tearDown() {
		parent::tearDown();
		$adapter = $this->getDatabaseAdapter();
		$adapter->query('DROP TABLE `_gmd_foobar`;');
	}
}
