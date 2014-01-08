<?php
/**
 * Mock model used by RelationManagerTest
 */
class Mocks_Model_RMThing extends Garp_Model_Db {
	protected $_name = '_tests_relation_manager_Thing';

	protected $_referenceMap = array(
		'Author' => array(
			'refTableClass' => 'Mocks_Model_RMUser',
			'columns' => 'author_id',
			'refColumns' => 'id'
		),
		'Modifier' => array(
			'refTableClass' => 'Mocks_Model_RMUser',
			'columns' => 'modifier_id',
			'refColumns' => 'id'
		)
	);
}
