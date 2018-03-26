<?php
/**
 * @package Tests
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Spawn_Db_Schema_Tables_SqliteTest extends Garp_Test_PHPUnit_TestCase {

    /**
     * @dataProvider renderCreateStatementProvider
     * @param  array  $args
     * @param  string $expected The SQL statement
     * @return void
     */
    public function testRenderCreateStatement(array $args, string $expected) {
        $schema = new Garp_Spawn_Db_Schema_Sqlite(
            $this->_getAdapter()
        );
        $this->assertEquals(
            $expected,
            $schema->tables()->renderCreateStatement(...$args)
        );
    }

    public function renderCreateStatementProvider() {
        return [
            [
                ['items', $this->_fieldsProvider1(), [], ['first_name']],
                $this->_createStatement1()
            ],
            [
                ['_poststags', $this->_fieldsProvider2(), $this->_relationsProvider2(), []],
                $this->_createStatement2()
            ]
        ];
    }

    protected function _fieldsProvider1() {
        return [
            new Garp_Spawn_Field('default', 'id', ['primary' => true, 'type' => 'numeric']),
            new Garp_Spawn_Field('config', 'first_name', ['maxLength' => 31]),
            new Garp_Spawn_Field(
                'config', 'options', ['type' => 'enum', 'options' => [
                    'red', 'green', 'blue'
                ], 'default' => 'green']
            ),
            new Garp_Spawn_Field(
                'config', 'description', []
            )
        ];
    }

    protected function _createStatement1() {
        return "CREATE TABLE `items` (
  `id` INTEGER NOT NULL AUTOINCREMENT,
  `first_name` TEXT NOT NULL UNIQUE,
  `options` TEXT NOT NULL DEFAULT 'green',
  `description` TEXT NOT NULL,
  PRIMARY KEY (`id`)
)";
    }

    protected function _fieldsProvider2() {
        return [
            new Garp_Spawn_Field('relation', 'post_id', ['primary' => true, 'type' => 'numeric']),
            new Garp_Spawn_Field('relation', 'tag_id', ['primary' => true, 'type' => 'numeric']),
        ];
    }

    protected function _relationsProvider2() {
        $postConfig = new Garp_Spawn_Config_Model_Base(
            'Post',
            $this->_getMockConfig('Post'),
            new Garp_Spawn_Config_Format_PhpArray()
        );
        $tagConfig = new Garp_Spawn_Config_Model_Base(
            'Tag',
            $this->_getMockConfig('Tag'),
            new Garp_Spawn_Config_Format_PhpArray()
        );
        return [
            new Garp_Spawn_Relation(
                new Garp_Spawn_Model_Base($postConfig),
                'Post',
                [
                    'type' => 'hasOne'
                ]
            ),
            new Garp_Spawn_Relation(
                new Garp_Spawn_Model_Base($tagConfig),
                'Tag',
                [
                    'type' => 'hasOne'
                ]
            )
        ];
    }

    protected function _createStatement2() {
        return "CREATE TABLE `_poststags` (
  `post_id` INTEGER NOT NULL,
  `tag_id` INTEGER NOT NULL,
  PRIMARY KEY (`post_id`,`tag_id`),
  FOREIGN KEY (`post_id`) REFERENCES `post`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`tag_id`) REFERENCES `tag`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
)";
    }

    protected function _getAdapter() {
        return new Zend_Db_Adapter_Pdo_Sqlite(
            [
                'dbname' => 'foo',
                'username' => 'foo',
                'password' => '******',
                'host' => 'localhost'
            ]
        );
    }

    protected function _getMockConfig(string $id) {
        return new Garp_Spawn_Config_Storage_PhpArray(
            [
                $id => [
                    'listFields' => ['foo'],
                    'inputs' => []
                ]
            ]
        );
    }
}

