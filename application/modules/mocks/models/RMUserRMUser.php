<?php
/**
 * Mock model used by RelationManagerTest
 */
class Mocks_Model_RMUserRMUser extends Garp_Model_Db {
	protected $_name = '_tests_relation_manager_UserUser';

	protected $_referenceMap = array(
		'User1' => array(
			'refTableClass' => 'Mocks_Model_RMUser',
			'columns' => 'user1_id',
			'refColumns' => 'id'
		),
		'User2' => array(
			'refTableClass' => 'Mocks_Model_RMUser',
			'columns' => 'user2_id',
			'refColumns' => 'id'
		)
	);
}
