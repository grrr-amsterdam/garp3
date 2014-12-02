<?php
/**
 * Garp_Model_Behavior_ImageScalable
 * Scales images.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Garp_Model_Behavior
 */
class Garp_Model_Behavior_ImageScalable extends Garp_Model_Behavior_Abstract {

	/**
 	 * Which column to read the image's filename from.
 	 * @var String
 	 */
	protected $_filename_column = 'filename';

	/**
 	 * Configure
 	 * @param Array $config
 	 */
	protected function _setup($config) {
		if (!empty($config['filename_column'])) {
			$this->_filename_column = $config['filename_column'];
		}		
	}

	public function afterInsert(&$args) {
		$model      = &$args[0];
		$data       = &$args[1];
		$primaryKey = &$args[2];

		$imageScaler = new Garp_Image_Scaler();
		$imageScaler->generateTemplateScaledImages($data[$this->_filename_column], $primaryKey);
	}

	public function afterUpdate(&$args) {
		$model        = &$args[0];
		$affectedRows = &$args[1];
		$data         = &$args[2];
		$where        = &$args[3];

		$row = $model->fetchRow($where);
		if ($row && $row->id && array_key_exists($this->_filename_column, $data)) {
			// The image itself has changed, therefore new scaled versions have to be generated.
			$imageScaler = new Garp_Image_Scaler();
			$imageScaler->generateTemplateScaledImages($data[$this->_filename_column], $row->id, true);
		}
	}

}
