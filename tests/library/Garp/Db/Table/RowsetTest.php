<?php
use Garp\Functional as f;

/**
 * @package Tests
 * @author  Harmen Janssen <harmen@grrr.nl>
 * @group Rowset
 */
class Garp_Db_Table_RowsetTest extends Garp_Test_PHPUnit_TestCase {
    public function testShouldFlatten() {
        $things = new Garp_Db_Table_Rowset(
            array(
            'data'     => array(
                array('id' => 3, 'name' => 'hendrik', 'intro' => 'lorem ipsum dolor sit amet'),
                array('id' => 2, 'name' => 'klaas', 'intro' => 'lorem ipsum dolor sit amet'),
                array('id' => 1, 'name' => 'henk', 'intro' => 'lorem ipsum dolor sit amet')
            ),
            'rowClass' => 'Garp_Db_Table_Row',
            )
        );

        $ids = $things->flatten('id');
        $this->assertEquals(array('3','2','1'), $ids);

        $idsAndNames = $things->flatten(array('id', 'name'));
        $this->assertEquals(
            array(
                array('id' => '3', 'name' => 'hendrik'),
                array('id' => '2', 'name' => 'klaas'),
                array('id' => '1', 'name' => 'henk')
            ),
            $idsAndNames
        );
    }

    public function testShouldMap() {
        $things = new Garp_Db_Table_Rowset(
            array(
            'data'     => array(
                array('id' => 3, 'name' => 'hendrik', 'intro' => 'lorem ipsum dolor sit amet'),
                array('id' => 2, 'name' => 'klaas', 'intro' => 'lorem ipsum dolor sit amet'),
                array('id' => 1, 'name' => 'henk', 'intro' => 'lorem ipsum dolor sit amet')
            ),
            'rowClass' => 'Garp_Db_Table_Row',
            )
        );

        $mappedThings = $things->map(
            function ($item) {
                $item['name'] = strtoupper($item['name']);
                return $item;
            }
        );
        $this->assertEquals('Garp_Db_Table_Rowset', get_class($mappedThings));
        $this->assertEquals('HENK', $mappedThings[2]['name']);
        $this->assertEquals('HENDRIK', $mappedThings[0]['name']);

        // $things should be unchanged
        $this->assertFalse($mappedThings === $things);
        $this->assertEquals('hendrik', $things[0]['name']);

        // can callback use local vars?
        $start = 0;
        $end = 1;
        $initials = $mappedThings->map(
            function ($item) use ($start, $end) {
                $item['name'] = substr($item['name'], $start, $end);
                return $item;
            }
        );
        $this->assertEquals('H', $initials[0]['name']);
        $this->assertEquals('K', $initials[1]['name']);
    }

    public function testShouldFilter() {
        $rows = new Garp_Db_Table_Rowset([
            'data' => [
                ['name' => 'koe'],
                ['name' => 'paard'],
            ],
        ]);
        $predicate = function (Zend_Db_Table_Row $row): bool {
            return strlen($row->name) > 3;
        };

        $filtered = $rows->filter($predicate);
        $this->assertCount(1, $filtered);
        $this->assertSame('paard', $filtered[0]->name);
    }

    public function testShouldConcat() {
        $these = new Garp_Db_Table_Rowset([
            'data' => [
                ['id' => 3, 'name' => 'cat'],
                ['id' => 2, 'name' => 'dog'],
                ['id' => 1, 'name' => 'bird']
            ],
            'rowClass' => 'Garp_Db_Table_Row',
        ]);
        $those = new Garp_Db_Table_Rowset([
            'data' => [
                ['id' => 4, 'name' => 'fox', 'foo' => 'bar'],
                ['id' => 5, 'name' => 'fish', 'foo' => 'bar']
            ],
            'rowClass' => 'Garp_Db_Table_Row',
        ]);

        $together = $these->concat($those);
        $this->assertEquals(
            ['bird', 'cat', 'dog', 'fish', 'fox'],
            f\sort($together->flatten('name'))
        );
        // Prove that records with different layout can be mixed
        $this->assertCount(2, $together->filter(f\prop('foo')));
        $this->assertCount(3, $together->filter(f\not(f\prop('foo'))));
    }

    public function testShouldPrependAndPush() {
        $rows = new Garp_Db_Table_Rowset([
            'data' => [
                ['id' => 3, 'name' => 'cat'],
                ['id' => 2, 'name' => 'dog'],
                ['id' => 1, 'name' => 'bird']
            ],
            'rowClass' => 'Garp_Db_Table_Row',
        ]);
        $appended = $rows->prepend(new Garp_Db_Table_Row([
            'data' => ['id' => 42, 'name' => 'hyena']
        ]));
        $this->assertEquals(
            [
                ['id' => 42, 'name' => 'hyena'],
                ['id' => 3, 'name' => 'cat'],
                ['id' => 2, 'name' => 'dog'],
                ['id' => 1, 'name' => 'bird'],
            ],
            $appended->toArray()
        );

        $appended2 = $appended->push(new Garp_Db_Table_Row([
            'data' => ['id' => 5, 'name' => 'goldfish']
        ]));
        $this->assertEquals(
            [
                ['id' => 42, 'name' => 'hyena'],
                ['id' => 3, 'name' => 'cat'],
                ['id' => 2, 'name' => 'dog'],
                ['id' => 1, 'name' => 'bird'],
                ['id' => 5, 'name' => 'goldfish'],
            ],
            $appended2->toArray()
        );
    }

    public function testShouldReduce() {
        $things = new Garp_Db_Table_Rowset([
            'data' => [
                ['amount' => 3],
                ['amount' => 2],
                ['amount' => 1],
            ]
        ]);

        $actual = $things->reduce(
            function (int $total, $row) {
                return $total + $row->amount;
            },
            0
        );

        $this->assertEquals(
            6,
            $actual
        );
    }

    public function testShouldReduceToRowset() {
        $things = new Garp_Db_Table_Rowset([
            'data' => [
                ['name' => 'koe'],
                ['name' => 'paard'],
                ['name' => 'paard'],
            ],
        ]);

        $deduplicate = function (Garp_Db_Table_Rowset $acc, $row) {
            $existingAnimals = $acc->filter(f\prop_equals('name', $row->name));
            if ($existingAnimals->count() !== 0) {
                return $acc;
            }
            return $acc->concat((new Garp_Db_Table_Rowset(['data' => [$row->toArray()]])));
        };

        $actual = $things->reduce(
            $deduplicate,
            new Garp_Db_Table_Rowset(['rowClass' => Garp_Db_Table_Row::class])
        );

        $this->assertInstanceOf(Garp_Db_Table_Rowset::class, $actual);
        $this->assertCount(2, $actual);

        $this->assertInstanceOf(Garp_Db_Table_Row::class, $actual[0]);
        $this->assertEquals('koe', $actual[0]->name);

        $this->assertInstanceOf(Garp_Db_Table_Row::class, $actual[1]);
        $this->assertEquals('paard', $actual[1]->name);

        $this->assertArrayNotHasKey(2, $actual);
    }

}
