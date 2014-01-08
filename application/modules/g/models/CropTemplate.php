<?php
/**
 * G_Model_CropTemplate
 * Model for CropTemplates (@see templates in application.ini)
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Model
 * @lastmodified $Date: $
 */
class G_Model_CropTemplate extends Garp_Model_IniFile {
	/**
	 * Which backend ini file to use
	 * @var String
	 */
	protected $_file = 'application.ini';
	
	
	/**
	 * Which namespace to use
	 * @var String
	 */
	protected $_namespace = 'image.template';
	
	
	/**
	 * Fetch all entries
	 * @return Array
	 */
	public function fetchAll() {
		$templates = parent::fetchAll();
		$out = array();
		$id  = 1;
		foreach ($templates as $key => $value) {
			if (array_key_exists('richtextable', $value) && $value['richtextable']) {
				$out[] = array(
					'id'	=> $id++,
					'name'	=> $key,
					'w'		=> !empty($value['w']) ? $value['w'] : null,
					'h'		=> !empty($value['h']) ? $value['h'] : null,
					'crop'	=> !empty($value['crop']) ? $value['crop'] : null,
					'grow'	=> !empty($value['grow']) ? $value['grow'] : null
				);
			}
		}
		return $out;
	}
}