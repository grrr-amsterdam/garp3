<?php
/**
 * Garp_Validate_LessThanOrEqualToTest
 * Tests Garp_Validate_LessThanOrEqualTo
 *
 * @package Tests
 * @author  Han Kortekaas <han@grrr.nl>
 * @group   Validate
 */
class Garp_Validate_LessThanOrEqualToTest extends Garp_Test_PHPUnit_TestCase {

    /**
     * @dataProvider numberProvider
     *
     * @param int|float $value
     * @param int|float $max
     * @param string    $assertion
     */
    public function testIsValid($value, $max, $assertion) {
        $validator = new Garp_Validate_LessThanOrEqualTo($max);

        $this->$assertion($validator->isValid($value));
    }

    public function numberProvider() {
        return [
            [2,   1,   'assertFalse'],
            [1,   2,   'assertTrue'],
            [2,   2,   'assertTrue'],
            [1.1, 1,   'assertFalse'],
            [1,   1.1, 'assertTrue'],
            [1.1, 1.1, 'assertTrue'],
            [1,   1.0, 'assertTrue'],
            [1.0, 1,   'assertTrue'],
            [2.3, 1.5, 'assertFalse'],
            [2.8, 7.4, 'assertTrue'],
            [5.3, 5.3, 'assertTrue']
        ];
    }
}
