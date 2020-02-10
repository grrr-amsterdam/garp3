<?php
/**
 * @package Garp
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Model_DbTest extends Garp_Test_PHPUnit_TestCase {

    /**
     * @dataProvider arrayToWhereClauseData
     * @param  array $input
     * @param  string $expected
     * @param  bool $useAnd
     * @return void
     */
    public function testArrayToWhereClause(array $input, string $expected, bool $useAnd) {
        $model = new class extends Garp_Model_Db {

        };
        $this->assertSame(
            $expected,
            $model->arrayToWhereClause($input, $useAnd)
        );
    }

    public function testEmptyRowset() {
        $model = new class extends Garp_Model_Db {
        };
        $this->assertEmpty($model->emptyRowset());
    }

    public function arrayToWhereClauseData() {
        return [
            [
                ['foo' => 'bar', 'baz' => 146, 'bla' => '🌼'],
                '"foo" = \'bar\' AND "baz" = 146 AND "bla" = \'🌼\'',
                true
            ],
            [
                ['foo' => null, 'bar' => 'bla'],
                '"foo" IS NULL AND "bar" = \'bla\'',
                true
            ],
            [
                ['foo' => null, 'bar' => 'bla'],
                '"foo" IS NULL OR "bar" = \'bla\'',
                false
            ],
            [
                ['foo' => ['1', '2', '3'], 'status' => 'published'],
                '"foo" IN (\'1\', \'2\', \'3\') AND "status" = \'published\'',
                true
            ]
        ];
    }

    public function setUp(): void {
        parent::setUp();

        $dbAdapter = $this->_getSqlite();
        Zend_Db_Table::setDefaultAdapter($dbAdapter);
    }

    protected function _getSqlite(): Zend_Db_Adapter_Pdo_Sqlite {
        return new Zend_Db_Adapter_Pdo_Sqlite(
            [
                'dbname' => ':memory:'
            ]
        );
    }


}
