<?php
/**
 * Garp_Validate_DateTest
 * Tests Garp_Validate_Date
 *
 * @package Tests
 * @author  Harmen Janssen <harmen@grrr.nl>
 * @group   Validate
 */
class Garp_Validate_DateTest extends Garp_Test_PHPUnit_TestCase {

    public function test_formats_should_match() {
        /**
         * Test a shitload of date formats
         */
        $dateFormats = array(
            array('d-m-Y', '11-02-1985'),
            array('d-m-Y', '01-10-2012'),
            array('d-m-Y', '11-10-894'),
            array('D j M y', 'Mon 1 Jan 85'),
            array('F \t\h\e jS', 'December the 24th'),
            array('\W\e\e\k W', 'Week 20'),
            array('F FF F', 'March JanuaryMay June'), // such a crazy example
            array('D j/n/y', 'Wed 30/1/94'),
            array('l d F', 'Saturday 22 October'),
        );
        foreach ($dateFormats as $i => $f) {
            $format = $f[0];
            $match = $f[1];
            $val = new Garp_Validate_Date($format);
            $this->assertTrue($val->isValid($match), "$match does not match date format $format");
        }
    }

    /**
     * This was actually a known bug, so I'm just including it here to make it sure it doesn't
     * happen again.
     *
     * @return void
     */
    public function test_known_failing_case() {
        $validator = new Garp_Validate_Date('j-n-Y');
        $this->assertFalse($validator->isValid('2016-12-14'));
    }

    public function test_should_show_human_readable_error() {
        $validator = new Garp_Validate_Date('d-m-Y', 'mm-dd-jjjj');
        $validator->isValid('banaan');

        $errorMessage = current($validator->getMessages());
        $this->assertEquals("'banaan' does not fit the date format 'mm-dd-jjjj'", $errorMessage);
    }

    public function test_should_validate_parsed_date() {
        $validator = new Garp_Validate_Date('d-m-Y', 'mm-dd-jjjj');
        $validator->validateParsedDate(true);
        $this->assertFalse($validator->isValid('31-02-1985'));
    }
}
