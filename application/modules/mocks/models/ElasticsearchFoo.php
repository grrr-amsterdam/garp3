<?php
class Mocks_Model_ElasticsearchFoo extends Garp_Model_Db {
	protected $_name = '_tests_elasticsearch_foo';

	/**
	 * @var Array $_mockRowData
	 */
	protected $_mockRowData = array(
		'name' => 'Modified Foo name',
		'description' => 'Modified Foo description'
	);

	protected $_bindable = array(
		'Mocks_Model_ElasticsearchBogus'
	);

	protected $_createStatement = "CREATE TABLE `_tests_elasticsearch_foo`(
		`id` int UNSIGNED NOT NULL AUTO_INCREMENT,
		`name` varchar(255),
		`description` varchar(255),
		`some_nonsense` varchar(255),
		PRIMARY KEY (`id`)) ENGINE=`InnoDB`;
		INSERT INTO `_tests_elasticsearch_foo` (`id`, `name`, `description`, `some_nonsense`)
		VALUES (1, 'Foo name 1', 'Foo description 1', 'Nonsense 1');
		INSERT INTO `_tests_elasticsearch_foo` (`id`, `name`, `description`, `some_nonsense`)
		VALUES (2, 'Foo name 2', 'Foo description 2', 'Nonsense 2');"
	;

	protected $_referenceMap = array(
	);

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
				'name' => 'description',
				'required' => true,
				'type' => 'text',
				'maxLength' => 100,
				'label' => 'Beschrijving',
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
		),
		'behaviors' => array(
			'Elasticsearchable' => array('name', 'description')
		),
		'relations' => array(
			'ElasticsearchBogus' => array(
				'model' => 'ElasticsearchBogus',
				'name' => 'ElasticsearchBogus',
				'type' => 'hasMany',
				'label' => 'ElasticsearchBogus',
				'limit' => null,
				'column' => 'main_foo_id',
				'simpleSelect' => null,
				'editable' => true,
				'inverse' => true,
				'oppositeRule' => 'MainFoo',
				'inverseLabel' => 'ElasticsearchFoo',
				'weighable' => false,
				'required' => false,
				'inputs' => null,
				'inline' => false,
				'mirrored' => true
			),
			'ElasticsearchBogus' => array(
				'model' => 'ElasticsearchBogus',
				'name' => 'ElasticsearchBogus',
				'type' => 'hasAndBelongsToMany',
				'label' => 'ElasticsearchBogus',
				'limit' => null,
				'column' => 'elasticsearch_bogus_id',
				'simpleSelect' => null,
				'editable' => true,
				'inverse' => true,
				'oppositeRule' => 'ElasticsearchFoo',
				'inverseLabel' => 'ElasticsearchFoo',
				'weighable' => false,
				'required' => false,
				'inputs' => null,
				'inline' => false,
				'mirrored' => true
			)
		),
		'unique' => null
	);

	public function init() {
		parent::init();
		$this->registerObserver(new Garp_Model_Behavior_Elasticsearchable(array('columns' => array('name', 'description'))));
	}

	public function getMockRowData() {
		return $this->_mockRowData;
	}

	public function getCreateStatement() {
		return $this->_createStatement;
	}	

}