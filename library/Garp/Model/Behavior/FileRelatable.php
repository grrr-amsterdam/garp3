<?php
/**
 * Garp_Model_Behavior_FileRelatable
 * This behavior indicates a record being related to a 
 * physical file on disc. 
 * The afterUpdate and afterDelete methods remove the 
 * lingering file.
 * 
 *  _  _      ___      ______    _____   
 * | \| ||   / _ \\   /_   _//  |  ___|| 
 * |  ' ||  | / \ ||  `-| |,-   | ||__   
 * | .  ||  | \_/ ||    | ||    | ||__   
 * |_|\_||   \___//     |_||    |_____|| 
 * `-` -`    `---`      `-`'    `-----`
 * 
 * This behavior can't be used until we figure out a 
 * way of determining wether a file isn't referenced 
 * by other records as well.
 * Till then this behavior's _setup method will throw an 
 * exception.
 * 
 * 
 * 
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Behavior
 * @lastmodified $Date: $
 */
class Garp_Model_Behavior_FileRelatable extends Garp_Model_Behavior_Abstract {
	/**
	 * The columns holding files
	 * @var Array
	 */
	protected $_fields = array();
	
	
	/**
	 * The new columns and values (saved beforeUpdate)
	 * @var Array
	 */
	protected $_newValues = array();
	
	
	/**
	 * The rows that are updated
	 * @var Garp_Db_Rowset
	 */
	protected $_affectedRows;
	
	
	/**
	 * Setup the behavioral environment
	 * @param Array $config Configuration options
	 * @return Void
	 */
	protected function _setup(array $config) {
		throw new Garp_Model_Behavior_Exception('This behavior may not be used yet. See note in docblock.');
		$this->_fields = $config;
	}
	
	
	/**
	 * Before update callback. Save the original file values here. 
	 * If they have changed afterUpdate, clear them.
	 * @param Array $options The new data is in $args[0]
	 * @return Array Or throw Exception if you wish to stop the insert
	 */
	public function beforeUpdate(array &$args) {
		$data = &$args[1];
		foreach ($data as $key => $value) {
			/**
			 * Save the current file value.
			 * If it differs from the given value, it will be cleared
			 * by afterUpdate.
			 */
			if (in_array($key, $this->_fields)) {
				$this->_newValues[$key] = $value;
				if (!$this->_affectedRows) {
					$model = $args[0];
					$where = $args[2];
					$affectedRows = $model->fetchAll($where);
					$this->_affectedRows = $affectedRows;
				}
			}
		}
	}
	
	
	/**
	 * After update callback. Clear the files if the columns are modified.
	 * We do this afterUpdate to make sure the update succeeded.
	 * @param Array $args The new data is in $args[1]
	 * @return Void
	 */
	public function afterUpdate(array &$args) {
		foreach ($this->_affectedRows as $row) {
			foreach ($this->_newValues as $key => $value) {
				// compare the old and new values
				if ($row->$key != $value) {
					$this->_deleteFile($row->$key);
				}
			}
		}
	}
	
	
	/**
	 * Remove a file.
	 * @param String $filename The filename
	 * @return Void
	 */
	protected function _deleteFile($filename) {
		$ini = Garp_Cache_Ini::factory(APPLICATION_PATH.'/configs/application.ini');
		$uploadPath = $ini->app->uploadsDirectory;
		$uploadPath = rtrim($uploadPath, '/\\').DIRECTORY_SEPARATOR;
		$filePath   = $uploadPath.$filename;
		if (file_exists($filePath) && is_file($filePath)) {
			unlink($filePath);
		}
	}
}