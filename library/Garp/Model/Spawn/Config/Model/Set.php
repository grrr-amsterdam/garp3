<?php
/**
 * A complete modelset configuration scheme with defaults.
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Model_Spawn_Config_Model_Set extends ArrayObject {
	/**
	 * @var	Garp_Model_Spawn_Config_Storage_Interface $_storage The storage interface for this model set.
	 */
	protected $_storage;
	
	/**
	 * @var Garp_Model_Spawn_Config_Format_Interface $_format The format in which files are stored.
	 */
	protected $_format;


	public function __construct(
		Garp_Model_Spawn_Config_Storage_Interface $storage,
		Garp_Model_Spawn_Config_Format_Interface $format
	) {
		$this->_storage = $storage;
		$this->_format = $format;
		
		$modelList = $this->_listModelIds();

		foreach ($modelList as $modelId) {
			$this[$modelId] = new Garp_Model_Spawn_Config_Model_Base($modelId, $storage, $format);
		}
	}
	

	/**
	 * Returns the storage interface
	 */
	public function getStorage() {
		return $this->_storage;
	}
	
	
	protected function _listModelIds() {
		$modelIds = array();
		$objectIds = $this->_storage->listObjectIds();
		
		foreach ($objectIds as $objectId) {
			$modelIds[] = $objectId;
		}
		
		return $modelIds;
	}
}