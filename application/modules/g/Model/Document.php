<?php
/**
 * Garp_Model_Document
 * Generic document model.
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class G_Model_Document extends Model_Base_Document {
	public function init() {
		$this->registerObserver(new Garp_Model_Behavior_Timestampable())
			 ->registerObserver(new Garp_Model_Validator_NotEmpty(array('filename')))
			 ;
		parent::init();
	}


	public function fetchFilenameById($id) {
		$row = $this->fetchRow($this->select()->where('id = ?', $id));
		if (isset($row->filename)) {
			return $row->filename;
		} else throw new Exception("Could not retrieve image record {$id}.");
	}
}
