<?php
/**
 * Mock model used by RelationManagerTest
 */
class Mocks_Model_RMUser extends Garp_Model_Db {
	protected $_name = '_tests_relation_manager_User';

	protected $_referenceMap = array(
		'Profile' => array(
			'refTableClass' => 'Mocks_Model_RMProfile',
			'columns' => 'profile_id',
			'refColumns' => 'id'
		)
	);
}
