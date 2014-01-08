<?php
/**
 * Garp_Util_StdClass
 * Like the stdClass, but can be fed an array of properties/values.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Util
 * @lastmodified $Date: $
 */
class Garp_Util_StdClass extends stdClass {
	/**
	 * Class constructor.
	 * @param Array $props Key => value pairs translate to properties. Only string keys are used.
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