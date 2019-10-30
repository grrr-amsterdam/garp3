
<?php
/**
 * Garp_Validate_DurationTest
 * Tests Garp_Validate_Duration
 *
 * @package Tests
 * @author  Martijn Gastkemper <martijn@grrr.nl>
 * @group   Validate
 */
class Garp_Validate_DurationTest extends Garp_Test_PHPUnit_TestCase {

    /**
     * @dataProvider data
     * 
     * @param mixed $value
     * @param bool $expected
     */
    public function test_validate($value, bool $expected) {
        $validator = new Garp_Validate_Duration();
        $this->assertSame($expected, $validator->isValid($value));
    }

    public function data() {
        return [
            [123, true],
            [123.0, true],
            ["asdf", false],
            ["123", true],
            ["123.0", true],
            [time() + 10, false],
            [time() - 10, true],
        ];
    }
}
