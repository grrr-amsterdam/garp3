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
 	 * Which templates are scaled synchronously? (default is asynchronously)
 	 * @var Array
 	 */
	protected $_synchronouslyScaledTemplates = array();

	/**
 	 * Configure
 	 * @param Array $config
 	 */
	protected function _setup($config) {
		if (!empty($config['filename_column'])) {
			$this->_filename_column = $config['filename_column'];
		}

		if (isset($config['synchronouslyScaledTemplates'])) {
			$this->_synchronouslyScaledTemplates = $config['synchronouslyScaledTemplates'];
		}
	}

	public function afterInsert(&$args) {
		$model      = &$args[0];
		$data       = &$args[1];
		$primaryKey = &$args[2];

		if (!array_key_exists($this->_filename_column, $data)) {
			return;
		}
		$this->scale($data[$this->_filename_column], $primaryKey);
	}

	public function afterUpdate(&$args) {
		$model        = &$args[0];
		$affectedRows = &$args[1];
		$data         = &$args[2];
		$where        = &$args[3];

		$row = $model->fetchRow($where);
		if ($row && $row->id && array_key_exists($this->_filename_column, $data)) {
			// The image itself has changed, therefore new scaled versions have to be generated.
			$this->scale($data[$this->_filename_column], $row->id);
		}
	}

	/**
 	 * Perform the scaling
 	 */
	public function scale($filename, $id) {
		$templates = instance(new Garp_Image_Scaler)->getTemplateNames();

		// Divide templates into sync ones and async ones
		$syncTemplates = array_intersect($templates, $this->_synchronouslyScaledTemplates);
		$asyncTemplates = array_diff($templates, $this->_synchronouslyScaledTemplates);

		foreach ($syncTemplates as $template) {
			$this->_scaleSync($filename, $id, $template);
		}

		$this->_scaleAsync($filename, $id);
	}

	protected function _scaleSync($filename, $id, $template) {
		return instance(new Garp_Image_Scaler)->scaleAndStore(
			$filename, $id, $template, true
		);
	}

	protected function _scaleAsync($filename, $id) {
		try {
			// Execute scaling in the background
			new Garp_Job_Background(
				'image generateScaled --filename=' . $filename
			);
		} catch (Garp_Job_Background_Exception $e) {
			// Recover by scaling sync
			return $this->_scaleSync($filename, $id, null);
		}
	}
}
