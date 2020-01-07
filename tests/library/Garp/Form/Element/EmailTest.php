<?php

/**
 * Class Garp_Form_Element_EmailTest
 *
 * @package Garp
 * @author Martijn Gastkemper <martijn@grrr.nl>
 */
class Garp_Form_Element_EmailTest extends Garp_Test_PHPUnit_TestCase {

    /**
     * @dataProvider validEmailAddresses
     *
     * @param string $email
     */
    public function testValidEmailAddresses(string $email) {
        $element = new Garp_Form_Element_Email('name');
        $this->assertTrue($element->isValid($email), $email);
    }

    public function validEmailAddresses() {
        return [
            ['test@example.com'],
            ['test+test@foo.example.com']
        ];
    }

    /**
     * @dataProvider invalidEmailAddresses
     *
     * @param string $email
     */
    public function testInvalidEmailAddresses(string $email) {
        $element = new Garp_Form_Element_Email('name');
        $this->assertFalse($element->isValid($email), $email);
    }

    public function invalidEmailAddresses() {
        return [
            ['test@localhost'],
            ['test@intranet.localhost'],
            ['test@127.0.0.1'],
            ['test@examplecom'],
        ];
    }
}
