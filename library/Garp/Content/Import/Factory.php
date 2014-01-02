<?php
/**
 * Garp_Content_Import_Factory
 * Generate a content importing class
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
class Garp_Content_Import_Factory {
	/**
	 * Return instance of Garp_Content_Import_Abstract
	 * @param String $dataFile Filename of the datafile
	 * @return Garp_Content_import_Abstract
	 */
	public static function getImporter($dataFile) {
		$type = self::mapExtensionToType($dataFile);
		
		// normalize type
		$className = 'Garp_Content_Import_'.ucfirst($type);
		$obj = new $className($dataFile);
		if (!$obj instanceof Garp_Content_Import_Abstract) {
			throw new Garp_Content_Import_Exception("Class $className does not implement Garp_Content_Import_Abstract.");
		}
		return $obj;
	}
	
	
	/**
	 * Map a file extension to a type of importer
	 * @param String $dataFile Filename of the datafile
	 * @return String
	 */
	public static function mapExtensionToType($dataFile) {
		$ext = substr(strrchr($dataFile, '.'), 1);
		$ext = strtolower($ext);
		switch ($ext) {
			case 'xls':
			case 'xlsx':
				return 'Excel';
			break;
			default:
				throw new Garp_Content_Import_Exception("Could not find importer for type $ext");
			break;
		}
	}
}