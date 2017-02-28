<?php
/**
 * @package Tests
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_ErrorHandlerTest extends Garp_Test_PHPUnit_TestCase {
    protected $_oldErrorReporting;

    public function setUp() {
        $this->_oldErrorReporting = error_reporting(E_ALL);
    }

    public function tearDown() {
        error_reporting($this->_oldErrorReporting);
    }

    /**
     * @expectedException Garp_Exception_RuntimeException_Warning
     */
    public function test_warning_exception() {
        array_key_exists(array(), 'banana');
    }

    /**
     * Tests E_STRICT warning.
     *
     * @deprecated Forget it. This does not work with PHP5.3 (it tries to load an instance
     * of the Garp_Exception_RuntimeException_Strict class but cannot find it somehow.
     * @return void
    public function test_strict_exception() {
        try {
            eval(
                'class A { function foo($abc) {} } class B extends A { function foo() {} }'
            );
        } catch (Exception $e) {
            // Apparently both of these are possible and it differs per PHP version
            $this->assertTrue(
                $e instanceof Garp_Exception_RuntimeException_Strict ||
                $e instanceof Garp_Exception_RuntimeException_Warning
            );
        }
    }
     */

    /**
     * @expectedException Garp_Exception_RuntimeException_Notice
     */
    public function test_notice_exception() {
        $a = array();
        $a['foo'];
    }

    public function test_another_notice_exception() {
        function change(&$var) {
            $var += 10;
        }
        try {
            $var = 1;
            change(++$var);
        } catch (Exception $e) {
            // Note: the type of exception differs between PHP versions
            $this->assertTrue(
                $e instanceof Garp_Exception_RuntimeException_Notice
                || $e instanceof Garp_Exception_RuntimeException_Strict
            );
        }
    }

    /**
     * @expectedException Garp_Exception_RuntimeException_UserError
     */
    public function test_user_error_exception() {
        trigger_error('Well darn.', E_USER_ERROR);
    }

    /**
     * @expectedException Garp_Exception_RuntimeException_RecoverableError
     */
    public function test_recoverable_exception() {
        $recoverable = new stdClass();
        (string)$recoverable;
    }

    public function test_deprecated_exception() {
        try {
            Foo::bar();
        } catch (Exception $e) {
            // Note: the type of exception differs between PHP versions
            $this->assertTrue(
                $e instanceof Garp_Exception_RuntimeException_Strict
                || $e instanceof Garp_Exception_RuntimeException_Deprecated
            );
        }
    }

    /**
     * @expectedException Garp_Exception_RuntimeException_Notice
     */
    public function test_with_at_suppressor() {
        $a = array();
        @$a['foo'];
    }

    /**
     * @expectedException Garp_Exception_RuntimeException_Warning
     */
    public function test_when_error_reporting_is_off() {
        error_reporting(0);
        array_key_exists(array(), 'banana');
    }

}

/**
 * @package Tests
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Foo {
    function bar() {
    }
}
