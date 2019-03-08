<?php
/**
 * Garp_Validate_GreaterThanOrEqualToTest
 * Tests Garp_Validate_GreaterThanOrEqualTo
 *
 * @package Tests
 * @author  Han Kortekaas <han@grrr.nl>
 * @group   Validate
 */
class Garp_Validate_GreaterThanOrEqualToTest extends Garp_Test_PHPUnit_TestCase {

    /**
     * @dataProvider numberProvider
     *
     * @param int|float $value
     * @param int|float $min
     * @param string    $assertion
     */
    public function testIsValid($value, $min, $assertion) {
        $validator = new Garp_Validate_GreaterThanOrEqualTo($min);

        $this->$assertion($validator->isValid($value));
    }

    public function numberProvider() {
        return [
            [2,   1,   'assertTrue'],
            [1,   2,   'assertFalse'],
            [2,   2,   'assertTrue'],
            [1.1, 1,   'assertTrue'],
            [1,   1.1, 'assertFalse'],
            [1.1, 1.1, 'assertTrue'],
            [1,   1.0, 'assertTrue'],
            [1.0, 1,   'assertTrue'],
            [2.3, 1.5, 'assertTrue'],
            [2.8, 7.4, 'assertFalse'],
            [5.3, 5.3, 'assertTrue']
        ];
    }
}
