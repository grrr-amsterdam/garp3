<?php
/**
 * This class test some custom assertions on the Garp TestCase abstract
 *
 * @author Ramiro <ramiro@grrr.nl>
 */
class GroupTest extends Garp_Test_PHPUnit_TestCase {

    /** @test */
    public function can_assert_if_arrays_are_equal_despite_the_order() {
        $expected = [1, 2, 3];
        $actual = [2, 3, 1];
        $this->assertEqualsCanonicalized(
            $expected,
            $actual
        );
    }

}