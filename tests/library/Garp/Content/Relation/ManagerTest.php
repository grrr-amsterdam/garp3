<?php
/**
 * Garp_Content_Relation_ManagerTest
 * class description
 *
 * @author       $Author: harmen $
 * @modifiedby   $LastChangedBy: harmen $
 * @version      $LastChangedRevision: 6306 $
 * @package      Garp
 * @lastmodified $LastChangedDate: 2012-09-17 22:49:11 +0200 (Mon, 17 Sep 2012) $
 * @group        Content
 */
class Garp_Content_Relation_ManagerTest extends Garp_Test_PHPUnit_TestCase {
	public function testRelateHasMany() {
		// insert mock values
		$userModel = new Mocks_Model_RMUser();
		$userId = $userModel->insert(array('name' => 'Frits'));
		$profileModel = new Mocks_Model_RMProfile();
		$profileId = $profileModel->insert(array('name' => 'A'));

		// Save relationship: Profile hasMany User
		Garp_Content_Relation_Manager::relate(array(
			'modelA' => $profileModel,
			'modelB' => $userModel,
			'keyA' => $profileId,
			'keyB' => $userId,
		));

		// Did it work?
		$user = $userModel->fetchRow($userModel->select()->where('id = ?', $userId));
		$this->assertEquals($user->profile_id, $profileId);
	}


	public function testRelateBelongsTo() {
		// insert mock values
		$userModel = new Mocks_Model_RMUser();
		$userId = $userModel->insert(array('name' => 'Frits'));
		$profileModel = new Mocks_Model_RMProfile();
		$profileId = $profileModel->insert(array('name' => 'A'));

		// Save relationship: User belongsTo Profile
		Garp_Content_Relation_Manager::relate(array(
			'modelA' => $userModel,
			'modelB' => $profileModel,
			'keyA' => $userId,
			'keyB' => $profileId,
		));

		// Did it work?
		$user = $userModel->fetchRow($userModel->select()->where('id = ?', $userId));
		$this->assertEquals($user->profile_id, $profileId);
	}


	public function testRelateHasAndBelongsToMany() {
		// insert mock values
		$userModel = new Mocks_Model_RMUser();
		$userId = $userModel->insert(array('name' => 'Harmen'));
		$tagModel = new Mocks_Model_RMTag();
		$tagIds = array();
		$tagIds[] = $tagModel->insert(array('name' => 'nerd'));
		$tagIds[] = $tagModel->insert(array('name' => 'father'));
		$tagIds[] = $tagModel->insert(array('name' => 'goatee'));

		// Relate 3 tags to the user
		foreach ($tagIds as $tagId) {
			Garp_Content_Relation_Manager::relate(array(
				'modelA' => $userModel,
				'modelB' => $tagModel,
				'keyA' => $userId,
				'keyB' => $tagId
			));
		}

		// Did it work?
		$userModel->bindModel('Mocks_Model_RMTag', array('modelClass' => 'Mocks_Model_RMTag'));
		$user = $userModel->fetchRow($userModel->select()->where('id = ?', $userId));
		$this->assertEquals(3, count($user->Mocks_Model_RMTag));
		$testTagIds = array();
		foreach ($user->Mocks_Model_RMTag as $tag) {
			$this->assertContains($tag->id, $tagIds);
			$testTagIds[] = $tag->id;
		}
		sort($testTagIds);
		sort($tagIds);
		$this->assertEquals($testTagIds, $tagIds);
	}


	/**
 	 * Duplicates as in: multiple relations between two models.
 	 */
	public function testRelateHasOneWithDuplicates() {
		// insert mock values
		$userModel = new Mocks_Model_RMUser();
		$userId1 = $userModel->insert(array('name' => 'Frits'));
		$userId2 = $userModel->insert(array('name' => 'Jaap'));
		$thingModel = new Mocks_Model_RMThing();
		$thingId = $thingModel->insert(array('name' => 'A'));

		// set 'Frits' as author
		Garp_Content_Relation_Manager::relate(array(
			'modelA' => $userModel,
			'modelB' => $thingModel,
			'keyA' => $userId1,
			'keyB' => $thingId,
			'rule' => 'Author'
		));

		$thing = $thingModel->fetchRow($thingModel->select()->where('id = ?', $thingId));
		$this->assertEquals($thing->author_id, $userId1);

		// set 'Jaap' as modifier
		Garp_Content_Relation_Manager::relate(array(
			'modelA' => $thingModel,
			'modelB' => $userModel,
			'keyA' => $thingId,
			'keyB' => $userId2,
			'rule' => 'Modifier'
		));

		$thing = $thingModel->fetchRow($thingModel->select()->where('id = ?', $thingId));
		$this->assertEquals($thing->modifier_id, $userId2);
	}


	public function testUnrelateHaseOneWithDuplicates() {
		// insert mock values
		$userModel = new Mocks_Model_RMUser();
		$userId1 = $userModel->insert(array('name' => 'Frits'));
		$userId2 = $userModel->insert(array('name' => 'Jaap'));
		$thingModel = new Mocks_Model_RMThing();
		$thingId = $thingModel->insert(array('name' => 'A'));

		// set 'Frits' as author
		Garp_Content_Relation_Manager::relate(array(
			'modelA' => $userModel,
			'modelB' => $thingModel,
			'keyA' => $userId1,
			'keyB' => $thingId,
			'rule' => 'Author'
		));

		// set 'Jaap' as modifier
		Garp_Content_Relation_Manager::relate(array(
			'modelA' => $thingModel,
			'modelB' => $userModel,
			'keyA' => $thingId,
			'keyB' => $userId2,
			'rule' => 'Modifier'
		));

		// destroy Author relationship
		Garp_Content_Relation_Manager::unrelate(array(
			'modelA' => $thingModel,
			'modelB' => $userModel,
			'keyA' => $thingId,
			'rule' => 'Author'
		));

		$thing = $thingModel->fetchRow($thingModel->select()->where('id = ?', $thingId));
		$this->assertEquals($thing->author_id, null);

		// destroy Modifier relationship
		Garp_Content_Relation_Manager::unrelate(array(
			'modelA' => $thingModel,
			'modelB' => $userModel,
			'keyA' => $thingId,
			'rule' => 'Modifier'
		));

		$thing = $thingModel->fetchRow($thingModel->select()->where('id = ?', $thingId));
		$this->assertEquals($thing->modifier_id, null);
	}
	

	public function testUnrelateHasMany() {
		/**
 	 	 * First, create some relationships
 	 	 * -----------------------------------
 	 	 */
		// insert mock values
		$userModel    = new Mocks_Model_RMUser();
		$userId1      = $userModel->insert(array('name' => 'Frits'));
		$userId2      = $userModel->insert(array('name' => 'Jaap'));
		$userId3      = $userModel->insert(array('name' => 'Ginneke'));
		$profileModel = new Mocks_Model_RMProfile();
		$profileId    = $profileModel->insert(array('name' => 'A'));

		// Save relationship: Profile hasMany User
		Garp_Content_Relation_Manager::relate(array(
			'modelA' => $profileModel,
			'modelB' => $userModel,
			'keyA' => $profileId,
			'keyB' => $userId1,
		));
		Garp_Content_Relation_Manager::relate(array(
			'modelA' => $profileModel,
			'modelB' => $userModel,
			'keyA' => $profileId,
			'keyB' => $userId2
		));
		Garp_Content_Relation_Manager::relate(array(
			'modelA' => $profileModel,
			'modelB' => $userModel,
			'keyA' => $profileId,
			'keyB' => $userId3
		));

		/**
 	 	 * Second, crush one of 'em
 	 	 * -----------------------------------
 	 	 */
		Garp_Content_Relation_Manager::unrelate(array(
			'modelA' => $profileModel,
			'modelB' => $userModel,
			'keyA' => $profileId,
			'keyB' => $userId1
		));

		// first dude lost his profile...
		$user1 = $userModel->fetchRow($userModel->select()->where('id = ?', $userId1));
		$this->assertNull($user1->profile_id);
		// ...second dude didn't
		$user2 = $userModel->fetchRow($userModel->select()->where('id = ?', $userId2));
		$this->assertEquals($user2->profile_id, $profileId);
		// ...third dude didn't
		$user3 = $userModel->fetchRow($userModel->select()->where('id = ?', $userId3));
		$this->assertEquals($user3->profile_id, $profileId);

		/**
 		 * Third, crush all of 'em by not specifying keyB
 		 * -----------------------------------
 		 */
		Garp_Content_Relation_Manager::unrelate(array(
			'modelA' => $profileModel,
			'modelB' => $userModel,
			'keyA' => $profileId
		));

		$user2 = $userModel->fetchRow($userModel->select()->where('id = ?', $userId2));
		$this->assertNull($user2->profile_id);
		$user3 = $userModel->fetchRow($userModel->select()->where('id = ?', $userId3));
		$this->assertNull($user3->profile_id);
	}


	public function testUnrelateBelongsTo() {
		// insert mock values
		$userModel    = new Mocks_Model_RMUser();
		$userId1      = $userModel->insert(array('name' => 'Frits'));
		$profileModel = new Mocks_Model_RMProfile();
		$profileId    = $profileModel->insert(array('name' => 'A'));

		// create relationship
		Garp_Content_Relation_Manager::relate(array(
			'modelA' => $userModel,
			'modelB' => $profileModel,
			'keyA' => $userId1,
			'keyB' => $profileId
		));

		// remove relationship
		Garp_Content_Relation_Manager::unrelate(array(
			'modelA' => $userModel,
			'modelB' => $profileModel,
			'keyA' => $userId1
		));

		$user1 = $userModel->fetchRow($userModel->select()->where('id = ?', $userId1));
		$this->assertNull($user1->profile_id);
	}


	public function testUnrelateHasAndBelongsToMany() {
		// insert mock values
		$userModel = new Mocks_Model_RMUser();
		$userId1 = $userModel->insert(array('name' => 'Harmen'));
		$userId2 = $userModel->insert(array('name' => 'David'));
		$tagModel = new Mocks_Model_RMTag();
		$tagIds = array();
		$tagIds[] = $tagModel->insert(array('name' => 'nerd'));
		$tagIds[] = $tagModel->insert(array('name' => 'father'));
		$tagIds[] = $tagModel->insert(array('name' => 'goatee'));

		// Relate 3 tags to the user1
		foreach ($tagIds as $tagId) {
			Garp_Content_Relation_Manager::relate(array(
				'modelA' => $userModel,
				'modelB' => $tagModel,
				'keyA' => $userId1,
				'keyB' => $tagId
			));
		}

		// Also relate tags to user2
		Garp_Content_Relation_Manager::relate(array(
			'modelA' => $userModel,
			'modelB' => $tagModel,
			'keyA' => $userId2,
			'keyB' => $tagIds[0]
		));
		Garp_Content_Relation_Manager::relate(array(
			'modelA' => $userModel,
			'modelB' => $tagModel,
			'keyA' => $userId2,
			'keyB' => $tagIds[2]
		));

		// Now, specifically unrelate one tag from user1
		Garp_Content_Relation_Manager::unrelate(array(
			'modelA' => $userModel,
			'modelB' => $tagModel,
			'keyA' => $userId1,
			'keyB' => $tagIds[2],
		));

		$adapter = $this->getDatabaseAdapter();
		$check = $adapter->fetchRow('SELECT COUNT(*) AS c FROM _tests_relation_manager_TagUser WHERE tag_id = '.
			$adapter->quote($tagIds[2]).' AND user_id = '.$adapter->quote($userId1));
		$this->assertEquals(0, (int)$check['c']);

		// Now, undo all relations that contain tag 1
		Garp_Content_Relation_Manager::unrelate(array(
			'modelA' => $userModel,
			'modelB' => $tagModel,
			'keyB' => $tagIds[0]
		));
		$check = $adapter->fetchRow('SELECT COUNT(*) AS c FROM _tests_relation_manager_TagUser WHERE tag_id = '.
			$adapter->quote($tagIds[0]));
		$this->assertEquals(0, (int)$check['c']);
	}


	public function testRelateHomo() {
		// insert mock values
		$userModel = new Mocks_Model_RMUser();
		$userId1 = $userModel->insert(array('name' => 'Frits'));
		$userId2 = $userModel->insert(array('name' => 'Jaap'));
		$userId3 = $userModel->insert(array('name' => 'Kees'));

		// make some friends
		Garp_Content_Relation_Manager::relate(array(
			'modelA' => $userModel,
			'modelB' => $userModel,
			'keyA' => $userId1,
			'keyB' => $userId2,
			'ruleA' => 'User1', // <-- rules are neccessary for Relation Manager to understand 
			'ruleB' => 'User2'  // which Mocks_Model_RMUser is which.
		));

		// did it work?
		$adapter = $this->getDatabaseAdapter();
		$check = $adapter->fetchRow('SELECT COUNT(*) AS c FROM _tests_relation_manager_UserUser WHERE user1_id = '.
			$adapter->quote($userId1).' AND user2_id = '.$adapter->quote($userId2));
		$this->assertEquals((int)$check['c'], 1);
		// should be bidirectional...
		$check = $adapter->fetchRow('SELECT COUNT(*) AS c FROM _tests_relation_manager_UserUser WHERE user1_id = '.
			$adapter->quote($userId2).' AND user2_id = '.$adapter->quote($userId1));
		$this->assertEquals((int)$check['c'], 1);
	}


	public function testUnrelateHomo() {
		// insert mock values
		$userModel = new Mocks_Model_RMUser();
		$userId1 = $userModel->insert(array('name' => 'Frits'));
		$userId2 = $userModel->insert(array('name' => 'Jaap'));
		$userId3 = $userModel->insert(array('name' => 'Kees'));
		$userId4 = $userModel->insert(array('name' => 'Gerard'));

		// make some friends
		Garp_Content_Relation_Manager::relate(array(
			'modelA' => $userModel,
			'modelB' => $userModel,
			'keyA' => $userId1,
			'keyB' => $userId2,
			'ruleA' => 'User1', // <-- rules are neccessary for Relation Manager to understand 
			'ruleB' => 'User2'  // which Mocks_Model_RMUser is which.
		));
		Garp_Content_Relation_Manager::relate(array(
			'modelA' => $userModel,
			'modelB' => $userModel,
			'keyA' => $userId1,
			'keyB' => $userId3,
			'ruleA' => 'User1',
			'ruleB' => 'User2'
		));
		Garp_Content_Relation_Manager::relate(array(
			'modelA' => $userModel,
			'modelB' => $userModel,
			'keyA' => $userId1,
			'keyB' => $userId4,
			'ruleA' => 'User1',
			'ruleB' => 'User2'
		));

		// destroy friendship 1
		Garp_Content_Relation_Manager::unrelate(array(
			'modelA' => $userModel,
			'modelB' => $userModel,
			'keyA' => $userId1,
			'keyB' => $userId2,
			'ruleA' => 'User1',
			'ruleB' => 'User2'
		));

		// did it work?
		$adapter = $this->getDatabaseAdapter();
		$check = $adapter->fetchRow('SELECT COUNT(*) AS c FROM `_tests_relation_manager_UserUser` WHERE user1_id = '.
			$adapter->quote($userId1).' AND user2_id = '.$adapter->quote($userId2));
		$this->assertEquals((int)$check['c'], 0);
		// should be deleted bidirectionally
		$check = $adapter->fetchRow('SELECT COUNT(*) AS c FROM `_tests_relation_manager_UserUser` WHERE user2_id = '.
			$adapter->quote($userId1).' AND user1_id = '.$adapter->quote($userId2));
		$this->assertEquals((int)$check['c'], 0);

		// destroy all user 1's friendships
		Garp_Content_Relation_Manager::unrelate(array(
			'modelA' => $userModel,
			'modelB' => $userModel,
			'keyA' => $userId1,
			'ruleA' => 'User1',
			'ruleB' => 'User2'
		));
		
		// did it work?
		$check = $adapter->fetchRow('SELECT COUNT(*) AS c FROM `_tests_relation_manager_UserUser` WHERE user1_id = '.
			$adapter->quote($userId1).' OR user2_id = '.$adapter->quote($userId1));
		$this->assertEquals((int)$check['c'], 0);
	}


	public function setUp() {
		$adapter = $this->getDatabaseAdapter();
		
		// Create tables and insert mock data
		$adapter->query('SET foreign_key_checks = 0;');
		$adapter->query('DROP TABLE IF EXISTS `_tests_relation_manager_Profile`;');
		$adapter->query(
		'CREATE TABLE `_tests_relation_manager_Profile` (
			`id` int UNSIGNED NOT NULL AUTO_INCREMENT,
			`name` varchar(20) NOT NULL,
			PRIMARY KEY (`id`)
		) ENGINE=`InnoDB`;');

		$adapter->query('DROP TABLE IF EXISTS `_tests_relation_manager_Tag`;');
		$adapter->query(
		'CREATE TABLE `_tests_relation_manager_Tag` (
			`id` int UNSIGNED NOT NULL AUTO_INCREMENT,
			`name` varchar(20) NOT NULL,
			PRIMARY KEY (`id`)
		) ENGINE=`InnoDB`;');

		$adapter->query('DROP TABLE IF EXISTS `_tests_relation_manager_User`;');
		$adapter->query('
		CREATE TABLE `_tests_relation_manager_User` (
			`id` int UNSIGNED NOT NULL AUTO_INCREMENT,
			`name` varchar(20) NOT NULL,
			`profile_id` int UNSIGNED,
			PRIMARY KEY (`id`),
		CONSTRAINT `profile_id` FOREIGN KEY (`profile_id`) REFERENCES `_tests_relation_manager_Profile` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
		) ENGINE=`InnoDB`;');

		$adapter->query('DROP TABLE IF EXISTS `_tests_relation_manager_TagUser`;');
		$adapter->query('
		CREATE TABLE `_tests_relation_manager_TagUser` (
			`user_id` int UNSIGNED NOT NULL,
			`tag_id` int UNSIGNED NOT NULL,
			PRIMARY KEY (`user_id`, `tag_id`),
			CONSTRAINT `user_id` FOREIGN KEY (`user_id`) REFERENCES `_tests_relation_manager_User` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
			CONSTRAINT `tag_id` FOREIGN KEY (`tag_id`) REFERENCES `_tests_relation_manager_Tag` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
		) ENGINE=`InnoDB`;
		');

		$adapter->query('DROP TABLE IF EXISTS `_tests_relation_manager_UserUser`;');
		$adapter->query('
		CREATE TABLE `_tests_relation_manager_UserUser` (
			`user1_id` int UNSIGNED NOT NULL,
			`user2_id` int UNSIGNED NOT NULL,
			PRIMARY KEY (`user1_id`, `user2_id`),
			CONSTRAINT `user1_id` FOREIGN KEY (`user1_id`) REFERENCES `_tests_relation_manager_User` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
			CONSTRAINT `user2_id` FOREIGN KEY (`user2_id`) REFERENCES `_tests_relation_manager_User` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
		) ENGINE=`InnoDB`;
		');

		$adapter->query('DROP TABLE IF EXISTS `_tests_relation_manager_Thing`;');
		$adapter->query('
		CREATE TABLE `_tests_relation_manager_Thing` (
			`id` int UNSIGNED NOT NULL AUTO_INCREMENT,
			`name` varchar(20) NOT NULL,
			`author_id` int UNSIGNED,
			`modifier_id` int UNSIGNED,
			PRIMARY KEY (`id`),
			CONSTRAINT `author_id` FOREIGN KEY (`author_id`) REFERENCES `_tests_relation_manager_User` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
			CONSTRAINT `modifier_id` FOREIGN KEY (`modifier_id`) REFERENCES `_tests_relation_manager_User` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
		) ENGINE=`InnoDB`;
		');

		$adapter->query('SET foreign_key_checks = 1;');
	}


	public function tearDown() {
		$adapter = $this->getDatabaseAdapter();
		$adapter->query('SET foreign_key_checks = 0;');
		$adapter->query('DROP TABLE `_tests_relation_manager_Profile`;');
		$adapter->query('DROP TABLE `_tests_relation_manager_User`;');
		$adapter->query('DROP TABLE `_tests_relation_manager_Tag`;');
		$adapter->query('DROP TABLE `_tests_relation_manager_TagUser`;');
		$adapter->query('DROP TABLE `_tests_relation_manager_UserUser`;');
		$adapter->query('DROP TABLE `_tests_relation_manager_Thing`;');
		$adapter->query('SET foreign_key_checks = 1;');
	}
}
