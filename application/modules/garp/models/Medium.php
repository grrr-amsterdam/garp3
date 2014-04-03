<?php
/**
 * G_Model_Medium
 * This model acts on a view called 'media'. 
 * This view is a union of 'images' and 'youtube_videos' and is not 
 * updatable. Therefore, insert, update and delete are disabled in 
 * this model.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Model
 * @lastmodified $Date: $
 */
class G_Model_Medium extends Garp_Model_Db {
	/**
	 * Name of the view
	 * @var String
	 */
	protected $_name = 'Medium';
	
	
	/**
	 * Primary key
	 * @var String
	 */
	protected $_primary = 'id';
	
	
	/**
	 * Fetch media related to another model.
	 * @param String $model The other model
	 * @param Mixed $primary The primary key
	 * @return Garp_Db_Table_Rowset
	 */
	public function fetchBy($modelName, $primary, $select = null) {
		$model				 = new $modelName();
		$imageBindingModel	 = $model->getBindingModel(new G_Model_Image());
		$youtubeBindingModel = $model->getBindingModel(new G_Model_YouTubeVideo());
		$imageBindingTable	 = $imageBindingModel->getName();
		$youtubeBindingTable = $youtubeBindingModel->getName();
		$modelToImageRef	 = $imageBindingModel->getReference($modelName);
		$modelToYoutubeRef	 = $youtubeBindingModel->getReference($modelName);
		
		// determine foreign keys;
		$primary = (array)$primary;
		$imageBindingConditions = $this->_getBindingConditions($modelToImageRef['columns'], $primary, $imageBindingTable);
		$youtubeBindingConditions = $this->_getBindingConditions($modelToYoutubeRef['columns'], $primary, $youtubeBindingTable);
		
		$select = $select ?: $this->select();
		$select->from($this->_name)
			   ->joinLeft($imageBindingTable, $imageBindingTable.".image_id = {$this->_name}.id", array())
			   ->joinLeft($youtubeBindingTable, $youtubeBindingTable.".youtube_video_id = {$this->_name}.id", array())
			   ->orWhere($imageBindingConditions)
			   ->orWhere($youtubeBindingConditions);
		return $this->fetchAll($select);
	}
	
	
	/**
	 * Generate binding conditions for the fetchBy method
	 * @param Array $columns The columns that are foreign keys in the binding table
	 * @param Array $primary Matching primary keys
	 * @param String $bindingTable The binding table
	 * @return String
	 */
	protected function _getBindingConditions($columns, $primary, $bindingTable) {
		$conditions = array();
		foreach ($columns as $i => $column) {
			if (empty($primary[$i])) {
				throw new Garp_Model_Exception('Primary key given is insufficient. Column '.$i. ' not given.');
			}
			$conditions[] = $this->getAdapter()->quoteInto("$bindingTable.$column = ?", $primary[$i]);
		}
		return implode(' AND ', $conditions);
	}
	
	
	/**
	 * The following methods disable updating of the view:
	 */
	public function insert(array $data) {
		throw new Garp_Model_Exception('Medium can not be inserted.');
	}
	
	
	public function update(array $data, $where) {
		throw new Garp_Model_Exception('Medium can not be updated.');
	}
	
	
    public function delete($where) {
		throw new Garp_Model_Exception('Medium can not be deleted.');
	}
}