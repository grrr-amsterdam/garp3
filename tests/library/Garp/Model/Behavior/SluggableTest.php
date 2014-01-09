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

		$this->assertEquals('henk-jan-de-beuker', $row->slug);
	}

	public function testShouldIncrementSlug() {
		$model = new Mocks_Model_SluggableTest();
		$model->registerObserver(new Garp_Model_Behavior_Sluggable(array(
			'baseField' => 'name'
		)));

		$model->insert(array('name' => 'henk jan de beuker'));
		$model->insert(array('name' => 'henk jan de beuker'));
		$row = $model->fetchRow($model->select()->order('id DESC'));

		$this->assertEquals('henk-jan-de-beuker-2', $row->slug);
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

		$this->assertEquals('henk-jan-de-beuker', $row->slug);
		$this->assertEquals('beukenlaan-20', $row->slug2);
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

		$this->assertEquals('henk-jan-de-beuker-beukenlaan-20', $row->slug);
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
		$this->assertEquals('henk-jan-de-beuker', $row->slug);

		$modelEn = new Mocks_Model_Sluggable2TestEn();
		$row = $modelEn->fetchRow();
		$this->assertEquals('henk-jan-de-beuker', $row->slug);
	}

	/**
 	 * A known bug occurred in the past when a second-language row
 	 * would be updated after it already existed in the primary language.
 	 * It would then not receive a new slug in the second language, if the 
 	 * baseField would not be specified in the second language. 
 	 * For instance: a record about Michael Jackson would receive the slug "michael-jackson" if 
 	 * the name is Michael Jackson. The second language doesn't have to 
 	 * update the name, but only the description. However, this would fail to
 	 * create the slug.
 	 * This test checks for that.
 	 */
	public function testShouldGenerateSlugForMultilingualModelAfterUpdate() {
		Zend_Controller_Front::getInstance()->setParam('locales', array('nl', 'en'));

		$model = new Mocks_Model_Sluggable2Test();
		// Save primary language first
		$id = $model->insert(array(
			'name' => array('nl' => 'Henk Jan De Beuker'),
			'tag' => array('nl' => 'nl__nl'),
			'something' => 'abc'
		));
		// Update with secondary language
		$model->update(array(
			'tag' => array('en' => 'en__en'),
			'something' => 'def' // <-- needed to generate valid UPDATE query... >.<
		), "`id` = '$id'");

		$i18nModel = new Mocks_Model_Sluggable2TestI18n();
		$row = $i18nModel->fetchRow(
			$i18nModel->select()->where('lang = "en"')->where('_sluggable_test_2_id = ?', $id)
		);
		// @note: since its the second rendition of the record, the slug will be incremented
		$this->assertEquals('henk-jan-de-beuker-2', $row->slug);
	}	

	public function testShouldGenerateSlugWithDate() {
		$model = $this->_getConfiguredModel(array(
			'baseField' => array(
				array(
					'column' => 'd',
					'type' => 'date'
				),
				'name'
			)
		));

		$model->insert(array('name' => 'henk jan de beuker', 'd' => '2013-10-12'));
		$row = $model->fetchRow();

		$this->assertEquals('12-10-2013-henk-jan-de-beuker', $row->slug); 
	}

	public function testShouldGenerateSlugWithFormattedDate() {
		$model = $this->_getConfiguredModel(array(
			'baseField' => array(
				array(
					'column' => 'd',
 				   	'format' => 'Y',
					'type' => 'date'
				),
				'name'
			)
		));

		$model->insert(array('name' => 'henk jan de beuker', 'd' => '2013-10-12'));
		$row = $model->fetchRow();

		$this->assertEquals('2013-henk-jan-de-beuker', $row->slug);
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
		Garp_Cache_Manager::purge();
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
			`d` date NULL,
			PRIMARY KEY (`id`)
		) ENGINE=`InnoDB`;');

		$dbAdapter->query('DROP TABLE IF EXISTS `_sluggable_test_2`;');
		$dbAdapter->query('
		CREATE TABLE `_sluggable_test_2`(
			`id` int UNSIGNED NOT NULL AUTO_INCREMENT,
			`something` CHAR(2) NULL,
			PRIMARY KEY (`id`)
		) ENGINE=`InnoDB`;');

		$dbAdapter->query('DROP TABLE IF EXISTS `_sluggable_test_2_i18n`;');
		$dbAdapter->query('
		CREATE TABLE `_sluggable_test_2_i18n`(
			`id` int UNSIGNED NOT NULL AUTO_INCREMENT,
			`name` varchar(124) NULL,
			`slug` varchar(124) NULL,
			`tag` varchar(20) NULL,
			`_sluggable_test_2_id` int UNSIGNED,
			`lang` CHAR(2),
			PRIMARY KEY (`id`)
		) ENGINE=`InnoDB`;');

		$dbAdapter->query('DROP VIEW IF EXISTS `_sluggable_test_2_nl`;');
		$dbAdapter->query("
		CREATE VIEW `_sluggable_test_2_nl` AS
		select
		`_sluggable_test_2`.`id` AS `id`,
		`_sluggable_test_2`.`something` AS `something`,
		`_sluggable_test_2_nl`.`name` AS `name`,
		`_sluggable_test_2_nl`.`slug` AS `slug`,
		`_sluggable_test_2_nl`.`tag` AS `tag`
		from (`_sluggable_test_2`
		left join `_sluggable_test_2_i18n` `_sluggable_test_2_nl`
		on(((`_sluggable_test_2_nl`.`_sluggable_test_2_id` = `_sluggable_test_2`.`id`) and (`_sluggable_test_2_nl`.`lang` = 'nl'))))");
		
		$dbAdapter->query('DROP VIEW IF EXISTS `_sluggable_test_2_en`;');
		$dbAdapter->query("
		CREATE VIEW `_sluggable_test_2_en` AS
		select
		`_sluggable_test_2`.`id` AS `id`,
		`_sluggable_test_2`.`something` AS `something`,
		if(((`_sluggable_test_2_en`.`name` <> '') and (`_sluggable_test_2_en`.`name` is not null)),`_sluggable_test_2_en`.`name`,`_sluggable_test_2_nl`.`name`) AS `name`,
		if(((`_sluggable_test_2_en`.`slug` <> '') and (`_sluggable_test_2_en`.`slug` is not null)),`_sluggable_test_2_en`.`slug`,`_sluggable_test_2_nl`.`slug`) AS `slug`,
		if(((`_sluggable_test_2_en`.`tag` <> '') and (`_sluggable_test_2_en`.`tag` is not null)),`_sluggable_test_2_en`.`tag`,`_sluggable_test_2_nl`.`tag`) AS `tag`
				
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
		Garp_Cache_Manager::purge();
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
