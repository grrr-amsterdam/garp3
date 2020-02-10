<?php
/**
 * @package Garp
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Db_Table_RowTest extends Garp_Test_PHPUnit_TestCase {

    public function test_wakeup() {
        $a = new class extends Garp_Model_Db {
            protected $_name = 'foo';
        };

        // Make sure this won't throw an exception due to the row not being connected.
        $row = serialize($a->createRow());
        $id = unserialize($row)->setFromArray(['name' => 'Joe'])
            ->save();
        $this->assertTrue(is_numeric($id));

        // Fetch partial result, and make sure the exception
        // "The specified Table does not have the same columns as the Row" is not thrown.
        $row = $a->fetchRow(
            $a->select()
                ->from('foo', ['name'])
        );
        $row = unserialize(serialize($row));
        // The row won't be connected, however, because we couldn't set the table.
        $this->assertFalse($row->isConnected());
    }

    public function setUp(): void {
        parent::setUp();

        $dbAdapter = $this->_getSqlite();
        Zend_Db_Table::setDefaultAdapter($dbAdapter);

        $dbAdapter->exec('CREATE TABLE foo (id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT, name VARCHAR, foo TEXT)');
    }

    public function tearDown(): void {
        parent::tearDown();

        $dbAdapter = Zend_Db_Table::getDefaultAdapter();
        $dbAdapter->exec('DROP TABLE foo');
    }

    protected function _getSqlite(): Zend_Db_Adapter_Pdo_Sqlite {
        return new Zend_Db_Adapter_Pdo_Sqlite(
            [
                'dbname' => ':memory:'
            ]
        );
    }
}
