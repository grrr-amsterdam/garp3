<?php
/**
 * Garp_Cache_PurgatoryTest
 * Tests both the Cache Purgatory and the Cachable behavior.
 *
 * @author       $Author: harmen $
 * @modifiedby   $LastChangedBy: harmen $
 * @version      $LastChangedRevision: 6428 $
 * @package      Garp
 * @lastmodified $LastChangedDate: 2012-09-24 14:51:43 +0200 (Mon, 24 Sep 2012) $
 * @group        Cache
 */
class Garp_Cache_PurgatoryTest extends Garp_Test_PHPUnit_TestCase {
	public function testPurgeAll() {
		$testKey = 'abcde';
		$testVal = '12345';
		$cacheFrontend = Zend_Registry::get('CacheFrontend');
		$cacheFrontend->save($testKey, $testVal);

		Garp_Cache_Purgatory::purge();

		$this->assertEquals(false, $cacheFrontend->load($testKey));
	}

	public function testPurgeByModel() {
		$dbAdapter = $this->getDatabaseAdapter();
		$modelThing = new Mocks_Model_CMThing();
		$modelThing->insert(array('name' => 'A'));

		// Create a cached result
		$cachedResult = $modelThing->fetchRow();
		$this->assertEquals('A', $cachedResult->name);

		// Update the result thru the backdoor. Hehehehe.
		$dbAdapter->query('UPDATE _tests_cache_purgatory_Thing SET name = \'B\'');

		// Confirm the result is unchanged
		$cachedResult = $modelThing->fetchRow();
		$this->assertEquals('A', $cachedResult->name);

		// Clear all the cache using the purgatory
		Garp_Cache_Purgatory::purge();

		// Confirm the result is changed
		$freshResult = $modelThing->fetchRow();
		$this->assertEquals('B', $freshResult->name);
	}

	public function setUp() {
		Garp_Cache_Purgatory::purge();

		$dbAdapter = $this->getDatabaseAdapter();

		$dbAdapter->query('SET foreign_key_checks = 0;');
		$dbAdapter->query('DROP TABLE IF EXISTS `_tests_cache_purgatory_Thing`;');
		$dbAdapter->query('
		CREATE TABLE `_tests_cache_purgatory_Thing`(
			`id` int UNSIGNED NOT NULL AUTO_INCREMENT,
			`name` varchar(20) NOT NULL,
			PRIMARY KEY (`id`)
		) ENGINE=`InnoDB`;');

		$dbAdapter->query('DROP TABLE IF EXISTS `_tests_cache_purgatory_FooBar`;');
		$dbAdapter->query('
		CREATE TABLE `_tests_cache_purgatory_FooBar`(
			`id` int UNSIGNED NOT NULL AUTO_INCREMENT,
			`name` varchar(20) NOT NULL,
			`thing_id` int UNSIGNED,
			FOREIGN KEY (`thing_id`) REFERENCES `_tests_cache_purgatory_Thing` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
			PRIMARY KEY (`id`)
		) ENGINE=`InnoDB`;');

		$dbAdapter->query('DROP TABLE IF EXISTS `_tests_cache_purgatory_FooBarThing`;');
		$dbAdapter->query('
		CREATE TABLE `_tests_cache_purgatory_FooBarThing`(
			`thing_id` int UNSIGNED NOT NULL,
			`foobar_id` int UNSIGNED NOT NULL,
			`tag` varchar(20),
			FOREIGN KEY (`thing_id`) REFERENCES `_tests_cache_purgatory_Thing` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
			FOREIGN KEY (`foobar_id`) REFERENCES `_tests_cache_purgatory_FooBar` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
			PRIMARY KEY (`thing_id`, `foobar_id`)
		) ENGINE=`InnoDB`;');
		$dbAdapter->query('SET foreign_key_checks = 1;');
	}

	public function tearDown() {
		Garp_Cache_Purgatory::purge();
		$dbAdapter = $this->getDatabaseAdapter();
		$dbAdapter->query('SET foreign_key_checks = 0;');
		$dbAdapter->query('DROP TABLE `_tests_cache_purgatory_Thing`;'); 
		$dbAdapter->query('DROP TABLE `_tests_cache_purgatory_FooBar`;'); 
		$dbAdapter->query('DROP TABLE `_tests_cache_purgatory_FooBarThing`;');
		$dbAdapter->query('SET foreign_key_checks = 1;');
	}
}
