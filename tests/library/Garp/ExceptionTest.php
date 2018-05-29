<?php
/**
 * @package Tests
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_ExceptionTest extends Garp_Test_PHPUnit_TestCase {

    public function test_marks_duplicate_entry_exception() {
        $this->assertTrue(
            Garp_Exception::isDuplicateEntryException(new Exception('Duplicate entry for key PRIMARY'))
        );
        $this->assertFalse(
            Garp_Exception::isDuplicateEntryException(new Exception('Something went wrong'))
        );
    }

}
