<?php
class Mocks_Model_ElasticsearchBogusElasticsearchFoo extends Garp_Model_Db {
	protected $_name = '_tests_elasticsearch_bogus_elasticsearch_foo';

	protected $_bindable = array('Mocks_Model_ElasticsearchBogus', 'Mocks_Model_ElasticsearchFoo');

	protected $_createStatement = "CREATE TABLE `_tests_elasticsearch_bogus_elasticsearch_foo` (
		  `elasticsearch_bogus_id` int(11) unsigned NOT NULL,
		  `elasticsearch_foo_id` int(11) unsigned NOT NULL,
		  PRIMARY KEY (`elasticsearch_bogus_id`,`elasticsearch_foo_id`)
		) ENGINE=InnoDB;
		INSERT INTO `_tests_elasticsearch_bogus_elasticsearch_foo` (`elasticsearch_bogus_id`, `elasticsearch_foo_id`) 
		VALUES (1, 1);"
	;

	protected $_referenceMap = array(
		'ElasticsearchBogus' => array(
			'columns' => 'elasticsearch_bogus_id',
			'refTableClass' => 'Mocks_Model_ElasticsearchBogus',
			'refColumns' => 'id'
		),
		'ElasticsearchFoo' => array(
			'columns' => 'elasticsearch_foo_id',
			'refTableClass' => 'Mocks_Model_ElasticsearchFoo',
			'refColumns' => 'id'
		)
	);

	public function init() {
		parent::init();
	}

	public function getCreateStatement() {
		return $this->_createStatement;
	}	

}
