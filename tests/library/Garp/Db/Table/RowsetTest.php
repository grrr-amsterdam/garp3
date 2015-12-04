<?php
/**
 * @group Rowset
 */
class Garp_Db_Table_RowsetTest extends Garp_Test_PHPUnit_TestCase {
	public function testShouldFlatten() {
		$mockModelThing = new Mocks_Model_Thing();
		$things = $mockModelThing->fetchAll($mockModelThing->select()->order('id DESC'));
		$this->assertCount(3, $things);

		$ids = $things->flatten('id');
		$this->assertEquals(array('3','2','1'), $ids);

		$idsAndNames = $things->flatten(array('id', 'name'));
		$this->assertEquals(array(
			array('id' => '3', 'name' => 'hendrik'),
			array('id' => '2', 'name' => 'klaas'),
			array('id' => '1', 'name' => 'henk')
		), $idsAndNames);
	}

	public function testShouldMap() {
		$mockModelThing = new Mocks_Model_Thing();
		$things = $mockModelThing->fetchAll($mockModelThing->select()->order('id DESC'));
		$this->assertCount(3, $things);

		$mappedThings = $things->map(function($item) {
			$item['name'] = strtoupper($item['name']);
			return $item;
		});
		$this->assertEquals('Garp_Db_Table_Rowset', get_class($mappedThings));
		$this->assertEquals('HENK', $mappedThings[2]['name']);
		$this->assertEquals('HENDRIK', $mappedThings[0]['name']);

		// $things should be unchanged
		$this->assertFalse($mappedThings === $things);
		$this->assertEquals('hendrik', $things[0]['name']);

		// can callback use local vars?
		$start = 0;
		$end = 1;
		$initials = $mappedThings->map(function($item) use ($start, $end) {
			$item['name'] = substr($item['name'], $start, $end);
			return $item;
		});
		$this->assertEquals('H', $initials[0]['name']);
		$this->assertEquals('K', $initials[1]['name']);
	}

	public function setUp() {
		$adapter = $this->getDatabaseAdapter();
		$adapter->query('SET foreign_key_checks = 0;');
		$adapter->query('DROP TABLE IF EXISTS `_tests_thing`;');
		$adapter->query(
		'CREATE TABLE `_tests_thing` (
			`id` int UNSIGNED NOT NULL AUTO_INCREMENT,
			`name` varchar(20) NOT NULL,
			`intro` text NOT NULL,
			PRIMARY KEY (`id`)
		) ENGINE=`InnoDB`;');

		$adapter->query('INSERT INTO _tests_thing (id, name, intro) VALUES(1, "henk",
 		   	"lorem ipsum dolor sit amet")');
		$adapter->query('INSERT INTO _tests_thing (id, name, intro) VALUES(2, "klaas",
			"lorem ipsum dolor sit amet")');
		$adapter->query('INSERT INTO _tests_thing (id, name, intro) VALUES(3, "hendrik",
 		   	"lorem ipsum dolor sit amet")');

		parent::setUp();
	}

	public function tearDown() {
		$adapter = $this->getDatabaseAdapter();
		$adapter->query('SET foreign_key_checks = 0;');
		$adapter->query('DROP TABLE `_tests_thing`;');
	}
}
