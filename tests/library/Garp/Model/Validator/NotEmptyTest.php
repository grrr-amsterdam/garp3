<?php
/**
 * @package Tests
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Model_Validator_NotEmptyTest extends Garp_Test_PHPUnit_TestCase {

    /**
     * @test
     * @expectedException Garp_Model_Validator_Exception
     */
    public function should_throw_on_empty_data() {
        $this->_getValidator()->validate(
            [],
            $this->_getMockModel()
        );
    }

    /**
     * @test
     * @expectedException Garp_Model_Validator_Exception
     */
    public function should_throw_on_empty_string() {
        $this->_getValidator()->validate(
            ['string' => '  '],
            $this->_getMockModel()
        );
    }

    /**
     * @test
     * @expectedException Garp_Model_Validator_Exception
     */
    public function should_throw_on_empty_number() {
        $this->_getValidator()->validate(
            ['number' => null],
            $this->_getMockModel()
        );
    }

    /**
     * @test
     * @expectedException Garp_Model_Validator_Exception
     */
    public function should_throw_on_empty_set() {
        $this->_getValidator()->validate(
            ['set' => []],
            $this->_getMockModel()
        );
    }

    /**
     * @test
     */
    public function should_accept_non_empty_values() {
        $this->assertNull(
            $this->_getValidator()->validate(
                ['set' => ['foo', 'bar'], 'number' => 0, 'string' => 'lorem ipsum'],
                $this->_getMockModel()
            )
        );
    }

    public function test_should_accept_string_containing_zero() {
        $this->assertNull(
            $this->_getValidator()->validate(
                [
                    'string' => '0',
                    'number' => 0,
                    'set' => ['value']
                ],
                $this->_getMockModel()
            )
        );
    }

    protected function _getValidator(): Garp_Model_Validator_NotEmpty {
        return new Garp_Model_Validator_NotEmpty(['string', 'number', 'set']);
    }

    protected function _getMockModel(): Garp_Model_Db {
        return new class extends Garp_Model_Db {
            protected $_db = 'foo';

            public function getFieldConfiguration($column = null) {
                $out = [
                    'string' => ['type' => 'text'],
                    'number' => ['type' => 'numeric'],
                    'set'    => ['type' => 'set']
                ];
                return $out[$column] ?? null;
            }
        };
    }


}
