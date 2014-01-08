<?php
/**
 * This class represents the storage of base models, all combined into a single file.
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Model
 */
class Garp_Spawn_Js_ModelSet_File_Base extends Garp_Spawn_Js_Model_File_Abstract {
	protected $_path = '/../public/js/models/base/';
	protected $_overwrite = true;
	protected $_filename = 'BaseModels.min';


	/**
	 * Override the constructor inheritance
	 */
	public function __construct() {}


	protected function _getFilePath() {
		return APPLICATION_PATH . $this->_path . $this->_filename . '.' . $this->_extension;
	}
}