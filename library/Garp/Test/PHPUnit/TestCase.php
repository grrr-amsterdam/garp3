<?php

use PHPUnit\Framework\TestCase;

/**
 * Garp_Test_PHPUnit_TestCase
 * Adds some convenience methods to unit tests.
 *
 * @package Garp_Test_PHPUnit
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
abstract class Garp_Test_PHPUnit_TestCase extends TestCase {

    use Garp_Test_Traits_UsesTestHelper;

}

