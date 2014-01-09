<?php
/**
 * Test Sluggable behavior
 * @author Harmen Janssen
 * @group Sluggable
 */
class Garp_Model_Behavior_SluggableTest extends Garp_Test_PHPUnit_TestCase {

	public function testShouldAutoGenerateSlug() {
		$model = $this->_getConfiguredModel(array(
			'baseField' => 'name'
		));

		$model->insert(array('name' => 'henk jan de beuker'));
		$row = $model->fetchRow();

		$this->assertEquals($row->slug, 'henk-jan-de-beuker');
	}

	public function testShouldIncrementSlug() {
		$model = new Mocks_Model_SluggableTest();
		$model->registerObserver(new Garp_Model_Behavior_Sluggable(array(
			'baseField' => 'name'
		)));

		$model->insert(array('name' => 'henk jan de beuker'));
		$model->insert(array('name' => 'henk jan de beuker'));
		$row = $model->fetchRow($model->select()->order('id DESC'));

		$this->assertEquals($row->slug, 'henk-jan-de-beuker-2');
	}

	public function testShouldGenerateMultipleSlugs() {
		$model = $this->_getConfiguredModel(array(
			'baseField' => array('name', 'address'),
			'slugField' => array('slug', 'slug2')
		));

		$model->insert(array(
			'name' => 'henk jan de beuker',
			'address' => 'beukenlaan 20'
		));
		$row = $model->fetchRow();

		$this->assertEquals($row->slug, 'henk-jan-de-beuker');
		$this->assertEquals($row->slug2, 'beukenlaan-20');
	}

	public function testShouldGenerateSlugFromMultipleFields() {
		$model = $this->_getConfiguredModel(array(
			'baseField' => array('name', 'address'),
			'slugField' => 'slug'
		));

		$model->insert(array(
			'name' => 'henk jan de beuker',
			'address' => 'beukenlaan 20'
		));
		$row = $model->fetchRow();

		$this->assertEquals($row->slug, 'henk-jan-de-beuker-beukenlaan-20');
	}

	public function testShouldGenerateSlugForMultilingualModel() {
		Zend_Controller_Front::getInstance()->setParam('locales', array('nl', 'en'));

		$model = new Mocks_Model_Sluggable2Test();
		// Save only Dutch name, slug should appear in both Dutch and English records
		$model->insert(array(
			'name' => array('nl' => 'Henk Jan De Beuker'),
		));

		$modelNl = new Mocks_Model_Sluggable2TestNl();
		$row = $modelNl->fetchRow();
		$this->assertEquals($row->slug, 'henk-jan-de-beuker');

		$modelEn = new Mocks_Model_Sluggable2TestEn();
		$row = $modelEn->fetchRow();
		$this->assertEquals($row->slug, 'henk-jan-de-beuker');
	}

	/**
 	 * A known bug occurred in the past when a second-language row
 	 * would be updated after it already existed in the primary language.
 	 * It would then not receive a new slug in the second language.
 	 * This test checks for that.
 	 */
	public function testShouldGenerateSlugForMultilingualModelAfterUpdate() {
		Zend_Controller_Front::getInstance()->setParam('locales', array('nl', 'en'));

		$model = new Mocks_Model_Sluggable2Test();
		// Save primary language first
		$id = $model->insert(array(
			'name' => array('nl' => 'Henk Jan De Beuker'),
			'tag' => 'nl__nl'
		));
		// Update with secondary language
		$model->update(array(
			'name' => array('en' => 'Hank John The Pounder'),
			'tag' => 'en__en'
		), "`id` = '$id'");

		$modelNl = new Mocks_Model_Sluggable2TestNl();
		$row = $modelNl->fetchRow();
		$this->assertEquals($row->slug, 'henk-jan-de-beuker');

		$modelEn = new Mocks_Model_Sluggable2TestEn();
		$row = $modelEn->fetchRow();
		$this->assertEquals($row->slug, 'hank-john-the-pounder');
	}	

	protected function _getConfiguredModel($config) {
		$model = new Mocks_Model_SluggableTest();
		$model->registerObserver(new Garp_Model_Behavior_Sluggable($config));
		return $model;
	}

	/**
 	 * Setup db tables
 	 */
	public function setUp() {
		$dbAdapter = $this->getDatabaseAdapter();

		$dbAdapter->query('SET foreign_key_checks = 0;');
		$dbAdapter->query('DROP TABLE IF EXISTS `_sluggable_test`;');
		$dbAdapter->query('
		CREATE TABLE `_sluggable_test`(
			`id` int UNSIGNED NOT NULL AUTO_INCREMENT,
			`name` varchar(124) NULL,
			`address` VARCHAR(124) NULL,
			`slug` varchar(124) NULL,
			`slug2` varchar(124) NULL,
			PRIMARY KEY (`id`)
		) ENGINE=`InnoDB`;');

		$dbAdapter->query('DROP TABLE IF EXISTS `_sluggable_test_2`;');
		$dbAdapter->query('
		CREATE TABLE `_sluggable_test_2`(
			`id` int UNSIGNED NOT NULL AUTO_INCREMENT,
			`tag` varchar(20) NULL,
			PRIMARY KEY (`id`)
		) ENGINE=`InnoDB`;');

		$dbAdapter->query('DROP TABLE IF EXISTS `_sluggable_test_2_i18n`;');
		$dbAdapter->query('
		CREATE TABLE `_sluggable_test_2_i18n`(
			`id` int UNSIGNED NOT NULL AUTO_INCREMENT,
			`name` varchar(124) NULL,
			`slug` varchar(124) NULL,
			`_sluggable_test_2_id` int UNSIGNED,
			`lang` CHAR(2),
			PRIMARY KEY (`id`)
		) ENGINE=`InnoDB`;');

		$dbAdapter->query('DROP VIEW IF EXISTS `_sluggable_test_2_nl`;');
		$dbAdapter->query("
		CREATE VIEW `_sluggable_test_2_nl` AS
		select
		`_sluggable_test_2`.`id` AS `id`,
		`_sluggable_test_2_nl`.`name` AS `name`,
		`_sluggable_test_2_nl`.`slug` AS `slug`
		from (`_sluggable_test_2`
		left join `_sluggable_test_2_i18n` `_sluggable_test_2_nl`
		on(((`_sluggable_test_2_nl`.`_sluggable_test_2_id` = `_sluggable_test_2`.`id`) and (`_sluggable_test_2_nl`.`lang` = 'nl'))))");
		
		$dbAdapter->query('DROP VIEW IF EXISTS `_sluggable_test_2_en`;');
		$dbAdapter->query("
		CREATE VIEW `_sluggable_test_2_en` AS
		select
		`_sluggable_test_2`.`id` AS `id`,
		if(((`_sluggable_test_2_en`.`name` <> '') and (`_sluggable_test_2_en`.`name` is not null)),`_sluggable_test_2_en`.`name`,`_sluggable_test_2_nl`.`name`) AS `name`,
		if(((`_sluggable_test_2_en`.`slug` <> '') and (`_sluggable_test_2_en`.`slug` is not null)),`_sluggable_test_2_en`.`slug`,`_sluggable_test_2_nl`.`slug`) AS `slug`
		from
		((`_sluggable_test_2`
		left join `_sluggable_test_2_i18n` `_sluggable_test_2_en`
		on(((`_sluggable_test_2_en`.`_sluggable_test_2_id` = `_sluggable_test_2`.`id`) and (`_sluggable_test_2_en`.`lang` = 'en')))) 
		left join `_sluggable_test_2_i18n` `_sluggable_test_2_nl`
		on(((`_sluggable_test_2_nl`.`_sluggable_test_2_id` = `_sluggable_test_2`.`id`) and (`_sluggable_test_2_nl`.`lang` = 'nl'))))");
	}

	/**
 	 * Destroy db tables
 	 */
	public function tearDown() {
		$dbAdapter = $this->getDatabaseAdapter();
		$dbAdapter->query('SET foreign_key_checks = 0;');
		$dbAdapter->query('DROP TABLE `_sluggable_test`;');
		$dbAdapter->query('DROP TABLE `_sluggable_test_2`;');
		$dbAdapter->query('DROP TABLE `_sluggable_test_2_i18n`;');
		$dbAdapter->query('DROP VIEW `_sluggable_test_2_nl`;');
		$dbAdapter->query('DROP VIEW `_sluggable_test_2_en`;');
		$dbAdapter->query('SET foreign_key_checks = 1;');
	}

}
