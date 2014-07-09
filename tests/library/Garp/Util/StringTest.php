<?php
/**
 * @group Util
 */
class Garp_Util_StringTest extends Garp_Test_PHPUnit_TestCase {

	public function testMatches() {
		// test match
		$this->assertTrue(Garp_Util_String::fuzzyMatch(
			'munchkin', 'm19302390iu23983n2893ch28302jdk2399i2903910hfwen'
		));
		// test non match
		$this->assertFalse(Garp_Util_String::fuzzyMatch(
			'munchkin', 'm19302390iu23983n2893ch28302jdk2399i2903910hfwe'
		));
		// another non match
		$this->assertFalse(Garp_Util_String::fuzzyMatch(
			'rugmuncher', 'm19302390iu23983n2893ch28302jdk2399i2903910hfwe'
		));
		// check with funky string
		$this->assertTrue(Garp_Util_String::fuzzyMatch(
			'(╯°□°）╯︵ ┻━┻', '(╯aejfekn°□°klewmwlkfm192）╯#(#)(#︵dsjkfwjkdn ┻━┻'
		));
		// don't match when case-sensitive
		$this->assertFalse(Garp_Util_String::fuzzyMatch(
			'munchkin', 'm19302390iU23983n2893ch28302jdk2399i2903910hfwen', false
		));
	}

}
