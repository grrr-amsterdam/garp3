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
		 	 ->registerObserver(new Garp_Model_Behavior_ImageScalable())
			 ->registerObserver(new Garp_Model_Validator_NotEmpty(array('filename')))
		 ;
		parent::init();
	}

	public function fetchFilenameById($id) {
		$row = $this->fetchRow($this->select()->where('id = ?', $id));
		if (!isset($row->filename)) {
			throw new Exception("Could not retrieve image record {$id}.");
		}
		return $row->filename;
	}
}
