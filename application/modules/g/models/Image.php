<?php
/**
 * Garp_Model_Image
 * Generic image model.
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class G_Model_Image extends Model_Base_Image {
	protected $_name = 'image';
	
	public function init() {
		$this->registerObserver(new Garp_Model_Behavior_Timestampable())
			 ->registerObserver(new Garp_Model_Validator_NotEmpty(array('filename')))
			 ;
		parent::init();
	}
	
	
	public function afterInsert(Array $args) {
		$data = $args[1];
		$primKey = $args[2];

		$imageScaler = new Garp_Image_Scaler();
		$imageScaler->generateTemplateScaledImages($data['filename'], $primKey);
	}


	public function afterUpdate(Array $args) {
		$data = $args[2];
		$where = $args[3];

		$row = $this->fetchRow($where);
		if ($row && $row->id && array_key_exists('filename', $data)) {
			//	the image itself has changed, therefore new scaled versions have to be generated.
			$imageScaler = new Garp_Image_Scaler();
			$imageScaler->generateTemplateScaledImages($data['filename'], $row->id, true);
		}
	}
	
	
	public function fetchFilenameById($id) {
		$row = $this->fetchRow($this->select()->where('id = ?', $id));
		if (isset($row->filename)) {
			return $row->filename;
		} else throw new Exception("Could not retrieve image record {$id}.");
	}
}