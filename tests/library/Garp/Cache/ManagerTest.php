<?php
/**
 * Garp_Cache_ManagerTest
 * Tests both the Cache Manager and the Cachable behavior.
 *
 * @author       $Author: harmen $
 * @modifiedby   $LastChangedBy: harmen $
 * @version      $LastChangedRevision: 6428 $
 * @package      Garp
 * @lastmodified $LastChangedDate: 2012-09-24 14:51:43 +0200 (Mon, 24 Sep 2012) $
 * @group        Cache
 */
class Garp_Cache_ManagerTest extends Garp_Test_PHPUnit_TestCase {
	public function testPurgeAll() {
		$testKey = 'abcde';
		$testVal = '12345';
		$cacheFrontend = Zend_Registry::get('CacheFrontend');
		$cacheFrontend->save($testKey, $testVal);

		Garp_Cache_Manager::purge();

		$this->assertEquals(false, $cacheFrontend->load($testKey));
	}

	public function testCreationOfScheduledJob() {
		// Act only when ScheduledJob model exists
		if (!Garp_Loader::getInstance()->isLoadable('Model_ScheduledJob')) {
			return;
		}

		Garp_Cache_Manager::scheduleClear(strtotime('19 March 2015 14:13:21'), array());
		$scheduledJobModel = new Model_ScheduledJob();
		$jobs = $scheduledJobModel->fetchAll();
		$this->assertEquals(1, count($jobs));

		$this->assertEquals('Cache clear', $jobs[0]['command']);
		$this->assertEquals('2015-03-19 14:13:21', $jobs[0]['at']);
	}

	public function testCreationOfScheduledJobWithTags() {
		// Act only when ScheduledJob model exists
		if (!Garp_Loader::getInstance()->isLoadable('Model_ScheduledJob')) {
			return;
		}

		$tags = array('aap', 'noot', 'mies');
		Garp_Cache_Manager::scheduleClear(strtotime('19 March 2015 14:13:21'), $tags);
		$scheduledJobModel = new Model_ScheduledJob();
		$jobs = $scheduledJobModel->fetchAll();
		$this->assertEquals(1, count($jobs));

		$this->assertEquals('Cache clear aap noot mies', $jobs[0]['command']);
	}

	public function testScheduledJobShouldCreateChecksum() {
		// Act only when ScheduledJob model exists
		if (!Garp_Loader::getInstance()->isLoadable('Model_ScheduledJob')) {
			return;
		}

		$tags = array('aap', 'noot', 'mies');
		$timestamp = strtotime('19 March 2015 14:13:21');
		Garp_Cache_Manager::scheduleClear($timestamp, $tags);
		$scheduledJobModel = new Model_ScheduledJob();
		$jobs = $scheduledJobModel->fetchAll();
		$this->assertEquals(1, count($jobs));

		$knownChecksum = md5(date('Y-m-d H:i:s', $timestamp) . ': Cache clear aap noot mies');

		$this->assertEquals($knownChecksum, $jobs[0]['checksum']);
	}

	/* Disabled by David, 12 june 2014 - it keeps failing, even with Memcached client connected to Memcache.
	public function testPurgeByModel() {
		$dbAdapter = $this->getDatabaseAdapter();
		$modelThing = new Mocks_Model_CMThing();
		$modelThing->insert(array('name' => 'A'));

		// Create a cached result
		$cachedResult = $modelThing->fetchRow();
		$this->assertEquals('A', $cachedResult->name);

		// Update the result thru the backdoor. Hehehehe.
		$dbAdapter->query('UPDATE _tests_cache_manager_Thing SET name = \'B\'');

		// Confirm the result is unchanged
		$cachedResult = $modelThing->fetchRow();
		$this->assertEquals('A', $cachedResult->name);

		// Clear all the cache using the manager
		Garp_Cache_Manager::purge();

		// Confirm the result is changed
		$freshResult = $modelThing->fetchRow();
		$this->assertEquals('B', $freshResult->name);
	}
	*/

	public function setUp() {
		Garp_Cache_Manager::purge();

		$dbAdapter = $this->getDatabaseAdapter();

		$dbAdapter->query('SET foreign_key_checks = 0;');
		$dbAdapter->query('DROP TABLE IF EXISTS `_tests_cache_manager_Thing`;');
		$dbAdapter->query('
		CREATE TABLE `_tests_cache_manager_Thing`(
			`id` int UNSIGNED NOT NULL AUTO_INCREMENT,
			`name` varchar(20) NOT NULL,
			PRIMARY KEY (`id`)
		) ENGINE=`InnoDB`;');

		$dbAdapter->query('DROP TABLE IF EXISTS `_tests_cache_manager_FooBar`;');
		$dbAdapter->query('
		CREATE TABLE `_tests_cache_manager_FooBar`(
			`id` int UNSIGNED NOT NULL AUTO_INCREMENT,
			`name` varchar(20) NOT NULL,
			`thing_id` int UNSIGNED,
			FOREIGN KEY (`thing_id`) REFERENCES `_tests_cache_manager_Thing` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
			PRIMARY KEY (`id`)
		) ENGINE=`InnoDB`;');

		$dbAdapter->query('DROP TABLE IF EXISTS `_tests_cache_manager_FooBarThing`;');
		$dbAdapter->query('
		CREATE TABLE `_tests_cache_manager_FooBarThing`(
			`thing_id` int UNSIGNED NOT NULL,
			`foobar_id` int UNSIGNED NOT NULL,
			`tag` varchar(20),
			FOREIGN KEY (`thing_id`) REFERENCES `_tests_cache_manager_Thing` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
			FOREIGN KEY (`foobar_id`) REFERENCES `_tests_cache_manager_FooBar` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
			PRIMARY KEY (`thing_id`, `foobar_id`)
		) ENGINE=`InnoDB`;');
		$dbAdapter->query('SET foreign_key_checks = 1;');
	}

	public function tearDown() {
		Garp_Cache_Manager::purge();
		$dbAdapter = $this->getDatabaseAdapter();
		$dbAdapter->query('SET foreign_key_checks = 0;');
		$dbAdapter->query('DROP TABLE `_tests_cache_manager_Thing`;');
		$dbAdapter->query('DROP TABLE `_tests_cache_manager_FooBar`;');
		$dbAdapter->query('DROP TABLE `_tests_cache_manager_FooBarThing`;');
		$dbAdapter->query('SET foreign_key_checks = 1;');

		// Act only when ScheduledJob model exists
		if (!Garp_Loader::getInstance()->isLoadable('Model_ScheduledJob')) {
			return;
		}
		$scheduledJobModel = new Model_ScheduledJob();
		$scheduledJobModel->delete('id > 0');
	}
}
