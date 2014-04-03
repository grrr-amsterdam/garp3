<?php
/**
 * Garp_Content_Import_Abstract
 * Blueprint for content importers
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
abstract class Garp_Content_Import_Abstract {
	/**
	 * The file from which to import
	 * @var String
	 */
	protected $_importFile;
	
	
	/**
	 * Class constructor
	 * @param String $fileName The datafile
	 * @return Void
	 */
	public function __construct($fileName) {
		$ini = new Zend_Config_Ini(APPLICATION_PATH.'/configs/application.ini', APPLICATION_ENV);
		//$uploadPath = $ini->app->uploadsDirectory;
		//@TODO: FIXME this uploadsDirectory doesn't exist anymore
		$uploadPath = APPLICATION_PATH.'/../public/uploads/documents';
		$this->_importFile = rtrim($uploadPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$fileName;
	}
	
	
	/**
	 * Return some sample data so an admin can provide 
	 * mapping of columns by example.
	 * @return Array
	 */
	abstract public function getSampleData();
	
	
	/**
	 * Insert data from importfile into database
	 * @param Garp_Model $model The imported data is for this model
	 * @param Array $mapping Mapping of import columns to table columns
	 * @param Array $options Various extra import options
	 * @return Boolean
	 */
	abstract public function save(Garp_Model $model, array $mapping, array $options);
}