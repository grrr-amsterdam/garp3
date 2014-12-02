<?php
/**
 * Mock model used by CacheManagerTest
 */
class Mocks_Model_CMFooBarCMThing extends Garp_Model_Db {
	protected $_name = '_tests_cache_manager_FooBarThing';

	protected $_referenceMap = array(
		'Thing' => array(
			'refTableClass' => 'Mocks_Model_CMThing',
			'refColumns' => 'id',
			'columns' => 'thing_id'
		),
		'FooBar' => array(
			'refTableClass' => 'Mocks_Model_CMFooBar',
			'refColumns' => 'id',
			'columns' => 'foobar_id'
		)
	);
}
