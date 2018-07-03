<?php

/**
 * Interface Garp_Model_DataFactory_Interface
 *
 * @package Garp
 * @author Martijn Gastkemper <martijn@grrr.nl>
 */
interface Garp_Model_DataFactory_Interface {
    public function make(array $defaults = []): array;
}
