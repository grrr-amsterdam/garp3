<?php
/**
 * Mock model used by RelationManagerTest
 */
class Mocks_Model_RMTagRMUser extends Garp_Model_Db {
	protected $_name = '_tests_relation_manager_TagUser';

	protected $_referenceMap = array(
		'Tag' => array(
			'refTableClass' => 'Mocks_Model_RMTag',
			'columns' => 'tag_id',
			'refColumns' => 'id'
		),
		'User' => array(
			'refTableClass' => 'Mocks_Model_RMUser',
			'columns' => 'user_id',
			'refColumns' => 'id'
		)
	);
}
