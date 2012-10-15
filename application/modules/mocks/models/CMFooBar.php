<?php
/**
 * Mock model used by CacheManagerTest
 */
class Mocks_Model_CMFooBar extends Garp_Model_Db {
	protected $_name = '_tests_cache_manager_FooBar';

	protected $_referenceMap = array(
		'Thing' => array(
			'refTableClass' => 'Mocks_Model_CMThing',
			'refColumns' => 'id',
			'columns' => 'thing_id'
		)
	);
}
