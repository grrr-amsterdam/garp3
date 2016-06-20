<?php
/**
 * @group Rowset
 */
class Garp_Db_Table_RowsetTest extends Garp_Test_PHPUnit_TestCase {
    public function testShouldFlatten() {
        $things = new Garp_Db_Table_Rowset(array(
            'data'     => array(
                array('id' => 3, 'name' => 'hendrik', 'intro' => 'lorem ipsum dolor sit amet'),
                array('id' => 2, 'name' => 'klaas', 'intro' => 'lorem ipsum dolor sit amet'),
                array('id' => 1, 'name' => 'henk', 'intro' => 'lorem ipsum dolor sit amet')
            ),
            'rowClass' => 'Garp_Db_Table_Row',
        ));

        $ids = $things->flatten('id');
        $this->assertEquals(array('3','2','1'), $ids);

        $idsAndNames = $things->flatten(array('id', 'name'));
        $this->assertEquals(array(
            array('id' => '3', 'name' => 'hendrik'),
            array('id' => '2', 'name' => 'klaas'),
            array('id' => '1', 'name' => 'henk')
        ), $idsAndNames);
    }

    public function testShouldMap() {
        $things = new Garp_Db_Table_Rowset(array(
            'data'     => array(
                array('id' => 3, 'name' => 'hendrik', 'intro' => 'lorem ipsum dolor sit amet'),
                array('id' => 2, 'name' => 'klaas', 'intro' => 'lorem ipsum dolor sit amet'),
                array('id' => 1, 'name' => 'henk', 'intro' => 'lorem ipsum dolor sit amet')
            ),
            'rowClass' => 'Garp_Db_Table_Row',
        ));

        $mappedThings = $things->map(function($item) {
            $item['name'] = strtoupper($item['name']);
            return $item;
        });
        $this->assertEquals('Garp_Db_Table_Rowset', get_class($mappedThings));
        $this->assertEquals('HENK', $mappedThings[2]['name']);
        $this->assertEquals('HENDRIK', $mappedThings[0]['name']);

        // $things should be unchanged
        $this->assertFalse($mappedThings === $things);
        $this->assertEquals('hendrik', $things[0]['name']);

        // can callback use local vars?
        $start = 0;
        $end = 1;
        $initials = $mappedThings->map(function($item) use ($start, $end) {
            $item['name'] = substr($item['name'], $start, $end);
            return $item;
        });
        $this->assertEquals('H', $initials[0]['name']);
        $this->assertEquals('K', $initials[1]['name']);
    }

}
