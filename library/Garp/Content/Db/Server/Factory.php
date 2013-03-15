<?php
/**
 * Garp_Content_Db_Server_Factory
 * Produces a Garp_Content_Db_Server_Local or Garp_Content_Db_Server_Remote instance.
 */
class Garp_Content_Db_Server_Factory {

	/**
	 * @param String $environment 		The environment id, f.i. 'development' or 'production'.
	 * @param String $otherEnvironment 	The environment of the counterpart server
	 * 									(i.e. target if this is source, and vice versa).
	 */
	public static function create($environment, $otherEnvironment) {
		if ($environment === 'development') {
			return new Garp_Content_Db_Server_Local($environment, $otherEnvironment);
		} else {
			return new Garp_Content_Db_Server_Remote($environment, $otherEnvironment);
		}
	}
}