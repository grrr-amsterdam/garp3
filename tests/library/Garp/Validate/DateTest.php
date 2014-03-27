<?php
/**
 * Garp_Validate_DateTest
 * Tests Garp_Validate_Date
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      Garp
 * @group Validate
 */
class Garp_Validate_DateTest extends Garp_Test_PHPUnit_TestCase {
		
	public function testFormatsShouldMatch() {
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
			array('l d F', 'Saturday 22 October')
		);
		foreach ($dateFormats as $i => $f) {
			$format = $f[0];
			$match = $f[1];
			$val = new Garp_Validate_Date($format);
			$this->assertTrue($val->isValid($match), "$match does not match date format $format");
		}
	}		

}
