<?php
/**
 * G_View_Helper_Date
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Helper
 * @lastmodified $Date: $
 */
class G_View_Helper_Date extends Zend_View_Helper_BaseUrl {	
	public function date() {
		return $this;
	}


	/**
	 * Formats dates according to configuration settings in the ini file.
	 * @param String $type Name of the format, as defined in the ini file. The ini value can be in either format.
	 * @param String $date MySQL datetime string
	 * @return String
	 */
	public function format($type, $date) {
		$ini = Garp_Cache_Ini::factory(APPLICATION_PATH.'/configs/application.ini', APPLICATION_ENV);
		$format = $ini->date->format->$type;

		if (strpos($format, '%') !== false) {
			return strftime($format, strtotime($date));
		} else {
			return date($format, strtotime($date));
		}
	}
}