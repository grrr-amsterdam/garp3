<?php
class Mocks_Model_ElasticsearchBogus extends Garp_Model_Db {
	protected $_name = '_tests_elasticsearch_bogus';

	/**
	 * @var Array $_mockRowData
	 */
	protected $_mockRowData = array(
		'name' => 'Modified Bogus name',
		'description' => 'Modified Bogus description'
	);

	protected $_bindable = array(
		'Mocks_Model_ElasticsearchFoo'
	);

	protected $_createStatement = "CREATE TABLE `_tests_elasticsearch_bogus`(
		`id` int UNSIGNED NOT NULL AUTO_INCREMENT,
		`name` varchar(255),
		`description` varchar(255),
		`main_foo_id` int UNSIGNED,
		PRIMARY KEY (`id`)) ENGINE=`InnoDB`;
		INSERT INTO `_tests_elasticsearch_bogus` (`id`, `name`, `description`, `main_foo_id`)
		VALUES (1, 'Bogus name', 'Bogus description', 2);"
	;

	protected $_configuration = array(
		'id' => 'ElasticsearchBogus',
		'fields' => array(
			array(
				'name' => 'name',
				'required' => true,
				'type' => 'text',
				'maxLength' => 100,
				'label' => 'Titel',
				'editable' => true,
				'visible' => true,
				'default' => null,
				'primary' => false,
				'unique' => false,
				'info' => null,
				'index' => null,
				'multilingual' => false,
				'comment' => null,
				'options' => array(),
				'float' => false,
				'unsigned' => true,
				'rich' => false,
				'origin' => 'config'
			),
			array(
				'name' => 'id',
				'required' => true,
				'type' => 'numeric',
				'maxLength' => 8,
				'label' => 'Id',
				'editable' => false,
				'visible' => false,
				'default' => null,
				'primary' => true,
				'unique' => false,
				'info' => null,
				'index' => true,
				'multilingual' => false,
				'comment' => null,
				'options' => array(),
				'float' => false,
				'unsigned' => true,
				'rich' => false,
				'origin' => 'config'
			),
			array(
				'name' => 'author_id',
				'required' => false,
				'type' => 'numeric',
				'maxLength' => null,
				'label' => 'Created by',
				'editable' => false,
				'visible' => false,
				'default' => null,
				'primary' => false,
				'unique' => false,
				'info' => null,
				'index' => null,
				'multilingual' => false,
				'comment' => null,
				'options' => array(),
				'float' => false,
				'unsigned' => true,
				'rich' => false,
				'origin' => 'relation'
			)
		),
		'behaviors' => array(
			'Elasticsearchable' => array(
				'columns' => array('name', 'description')
			)
		),
		'relations' => array(
			'MainFoo' => array(
				'model' => 'ElasticsearchFoo',
				'name' => 'MainFoo',
				'type' => 'hasOne',
				'label' => 'Main Foo',
				'limit' => 1,
				'column' => 'main_foo_id',
				'simpleSelect' => null,
				'editable' => true,
				'inverse' => false,
				'oppositeRule' => 'MainFoo',
				'inverseLabel' => 'Bogus',
				'weighable' => false,
				'required' => false,
				'inputs' => null,
				'inline' => false,
				'mirrored' => false
			),
			'ElasticsearchFoo' => array(
				'model' => 'ElasticsearchFoo',
				'name' => 'ElasticsearchFoo',
				'type' => 'hasAndBelongsToMany',
				'label' => 'Foo',
				'limit' => null,
				'column' => 'foo_id',
				'simpleSelect' => null,
				'editable' => true,
				'inverse' => true,
				'oppositeRule' => 'ElasticsearchBogus',
				'inverseLabel' => 'ElasticsearchBogus',
				'weighable' => false,
				'required' => false,
				'inputs' => null,
				'inline' => false,
				'mirrored' => false
			)
		),
		'unique' => null
	);

	protected $_referenceMap = array(
		'MainFoo' => array(
			'refTableClass' => 'Mocks_Model_ElasticsearchFoo',
			'columns' => 'main_foo_id',
			'refColumns' => 'id'
		),
	);


	public function init() {
		parent::init();
		$this->registerObserver(new Garp_Model_Behavior_Elasticsearchable(array(
			'columns' => array('name', 'description'),
			'rootable' => true
		)));
	}

	public function getMockRowData() {
		return $this->_mockRowData;
	}	

	public function getCreateStatement() {
		return $this->_createStatement;
	}	
}