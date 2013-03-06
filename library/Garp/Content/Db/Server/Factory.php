<?php
/**
 * Garp_Content_Db_Server_Factory
 * Produces a Garp_Content_Db_Server_Local or Garp_Content_Db_Server_Remote instance.
 */
class Garp_Content_Db_Server_Factory {

	/**
	 * @param String $environment The environment id, f.i. 'development' or 'production'.
	 */
	public static function create($environment) {
		if ($environment === 'development') {
			return new Garp_Content_Db_Server_Local($environment);
		} else {
			return new Garp_Content_Db_Server_Remote($environment);
		}
	}	
}