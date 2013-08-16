<?php
/**
 * Garp_Content_Upload_Storage_Factory
 * Produces a Garp_Content_Upload_Storage_* instance.
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
class Garp_Content_Upload_Storage_Factory {

	/**
	 * @param String $environment The environment id, f.i. 'development' or 'production'.
	 */
	public static function create($environment) {
		$ini = new Zend_Config_Ini(APPLICATION_PATH.'/configs/application.ini', $environment);
		$cdnType = $ini->cdn->type;
		
		switch($cdnType) {
			case 'local':
				if ($environment === 'development') {
					return new Garp_Content_Upload_Storage_Type_LocalWebserver($environment);
				} else {
					return new Garp_Content_Upload_Storage_Type_RemoteWebserver($environment);
				}
			break;
			case 's3':
				return new Garp_Content_Upload_Storage_Type_S3($environment);
			break;
			default:
				throw new Exception('Unknown CDN type for environment ' . $environment);
		}
	}	
}