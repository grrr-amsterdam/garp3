<?php
/**
 * @package Garp
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Model_DbTest extends Garp_Test_PHPUnit_TestCase {

    public function testBindModel() {
        $mockPost = new Mock_Model_Post();
        $comment = new Mock_Model_Comment();

        $postId = $mockPost->insert(['title' => 'Hello World', 'body' => 'This is the first post']);
        $mockPost->bindModel('Comments', ['modelClass' => 'Mock_Model_Comment']);

        $posts = $mockPost->fetchAll();
        $this->assertCount(1, $posts);
        $this->assertInstanceOf('Garp_Db_Table_Rowset', $posts[0]['Comments']);

        $comment->insert(['body' => 'Lorem ipsum', 'post_id' => $postId]);
        $comment->insert(['body' => 'Very nice post', 'post_id' => $postId]);
        $comment->insert(['body' => 'I disagree', 'post_id' => $postId]);

        $posts = $mockPost->fetchAll();
        $this->assertSame(
            [
                'Lorem ipsum',
                'Very nice post',
                'I disagree'
            ],
            $posts[0]['Comments']->flatten('body')
        );
    }

    public function setUp() {
        parent::setUp();

        $dbAdapter = $this->_getSqlite();
        Zend_Db_Table::setDefaultAdapter($dbAdapter);

        $this->_createSchema($dbAdapter, BASE_PATH . '/tests/mocks/data/posts.sqlite.sql');
        $this->_createSchema($dbAdapter, BASE_PATH . '/tests/mocks/data/comments.sqlite.sql');
    }

    public function tearDown() {
        parent::tearDown();

        $dbAdapter = $this->_getSqlite();
        $this->_dropSchema($dbAdapter, 'posts');
        $this->_dropSchema($dbAdapter, 'comments');
    }

    protected function _getSqlite(): Zend_Db_Adapter_Pdo_Sqlite {
        return new Zend_Db_Adapter_Pdo_Sqlite(
            [
                'dbname' => ':memory:'
            ]
        );
    }

    protected function _createSchema(Zend_Db_Adapter_Abstract $dbAdapter, string $path) {
        $schemaSql = file_get_contents($path);
        $dbAdapter->getConnection()->exec($schemaSql);
    }

    protected function _dropSchema(Zend_Db_Adapter_Abstract $dbAdapter, string $table) {
        $dbAdapter->getConnection()->exec("DROP TABLE IF EXISTS {$table}");
    }
}
