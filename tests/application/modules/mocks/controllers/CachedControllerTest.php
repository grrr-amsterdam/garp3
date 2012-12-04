<?php
/**
 * G_CachedControllerTest
 * Tests the static caching of controller actions.
 *
 * @author       $Author: harmen $
 * @modifiedby   $LastChangedBy: harmen $
 * @version      $LastChangedRevision: 6428 $
 * @package      Garp
 * @subpackage   Test
 * @lastmodified $LastChangedDate: 2012-09-24 14:51:43 +0200 (Mon, 24 Sep 2012) $
 * @group        Controller
 *
 */
class G_CachedControllerTest extends Garp_Test_PHPUnit_ControllerTestCase {
	protected $_cachePath; 

	public function testRouteShouldBeCached() {
		$this->dispatch('/mocks/staticcache/index/');
		$this->assertController('staticcache');

		// Flush the buffer manually: this triggers creation of cache files
		ob_end_flush();

		$this->assertTrue(file_exists($this->_cachePath.'/mocks/staticcache/index.html'));
	}

	public function testPurgeShouldClearCache() {
		$this->dispatch('/mocks/staticcache/index/');
		$this->assertController('staticcache');

		// Flush the buffer manually: this triggers creation of cache files
		ob_end_flush();

		$this->assertTrue(file_exists($this->_cachePath.'/mocks/staticcache/index.html'));

		Garp_Cache_Manager::purge();

		$this->assertFalse(file_exists($this->_cachePath.'/mocks/staticcache/index.html'));
	}

	public function setUp() {
		parent::setUp();
		$this->_cachePath = GARP_APPLICATION_PATH.'/../tests/tmp';

		// Store static HTML cache in handily accessible location
		if (!is_writable($this->_cachePath)) {
			if (!chmod($this->_cachePath, 0777)) {
				throw new Exception('Cache dir is not writable. Cannot execute test.');
			}
		}
		$pageCache = $this->getPageCache();
		$pageCache->getBackend()
			->setOption('public_dir', $this->_cachePath)
			->setOption('disable_caching', false)
		;

		// start with an empty cache
		Garp_Cache_Manager::purgeMemcachedCache();
		Garp_Cache_Manager::purgeStaticCache(array(), $this->_cachePath);

		// create necessary tables
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
		parent::tearDown();
		Garp_Cache_Manager::purgeMemcachedCache();
		Garp_Cache_Manager::purgeStaticCache(array(), $this->_cachePath);
		
		$dbAdapter = $this->getDatabaseAdapter();
		$dbAdapter->query('SET foreign_key_checks = 0;');
		$dbAdapter->query('DROP TABLE `_tests_cache_manager_Thing`;'); 
		$dbAdapter->query('DROP TABLE `_tests_cache_manager_FooBar`;'); 
		$dbAdapter->query('DROP TABLE `_tests_cache_manager_FooBarThing`;');
		$dbAdapter->query('SET foreign_key_checks = 1;');
	}

	protected function getPageCache() {
		$cacheManager = $this->getFrontController()
			->getParam('bootstrap')
			->getResource('cachemanager')
		;
		$pageCache = $cacheManager->getCache(Zend_Cache_Manager::PAGECACHE);
		return $pageCache;
	}
}
