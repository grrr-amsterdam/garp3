<?php
/**
 * Garp_Util_StdClass
 * Like the stdClass, but can be fed an array of properties/values.
 *
 * @package Garp_Util
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Util_StdClass extends stdClass {
    /**
     * Class constructor.
     *
     * @param array $props Key => value pairs translate to properties. Only string keys are used.
     * @return Void
     */
    public function __construct(array $props) {
        foreach ($props as $prop => $val) {
            if (is_string($prop)) {
                $this->{$prop} = $val;
            }
        }
    }
}
