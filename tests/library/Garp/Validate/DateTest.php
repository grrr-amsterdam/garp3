<?php
/**
 * Garp_Validate_DateTest
 * Tests Garp_Validate_Date
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      Garp
 * @group        Validate
 */
class Garp_Validate_DateTest extends Garp_Test_PHPUnit_TestCase {
		
	public function formatsShouldMatch() {
		$dateFormats = array(
			array('d-m-Y', '11-02-1985'),
			array('d-m-Y', '01-10-2012'),
			array('d-m-Y', '11-10-894'),
			array('D j M y', 'Mon 1 Jan 85')
		);
		foreach ($dateFormats as $i => $f) {
			$format = $f[0];
			$match = $f[1];
			$val = new Garp_Validate_Date($format);
			$this->assertTrue($val->isValid($match));
		}
	}		

}
