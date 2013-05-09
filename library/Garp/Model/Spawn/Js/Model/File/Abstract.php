<?php
/**
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Model
 */
abstract class Garp_Model_Spawn_Js_Model_File_Abstract {
	protected $_extension = 'js';
	protected $_overwrite = false;
	protected $_model;
	
	
	public function __construct(Garp_Model_Spawn_Model_Base $model) {
		$this->_model = $model;
	}
	
	
	public function save($content) {
		$filePath = $this->_getFilePath();

		if (
			$this->_overwrite ||
			!file_exists($filePath)
		) {
			if (file_put_contents($filePath, $content) !== false) {
				return true;
			} else {
			 	throw new Exception("Could not write to ".$filePath);
			}
		}

		return false;
	}
	
	
	protected function _getFilePath() {
		return APPLICATION_PATH.$this->_path.$this->_model->id.'.'.$this->_extension;
	}
}