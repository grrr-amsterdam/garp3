<?php
/**
 * A complete modelset configuration scheme with defaults.
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Spawn_Config_Model_Set extends ArrayObject {
	const DEFAULT_EXTENSION 	= 'json';
	const DEFAULT_CONFIG_PATH 	= '/modules/default/models/config/';
	
	/**
	 * @var	Garp_Spawn_Config_Storage_Interface $_storage The storage interface for this model set.
	 */
	protected $_storage;
	
	/**
	 * @var Garp_Spawn_Config_Format_Interface $_format The format in which files are stored.
	 */
	protected $_format;


	public function __construct(
		Garp_Spawn_Config_Storage_Interface $storage = null,
		Garp_Spawn_Config_Format_Interface $format = null
	) {
		if (!$storage) {
			$storage = $this->_getDefaultStorage();
		}

		if (!$format) {
			$format = $this->_getDefaultFormat();
		}
		
		$this->setStorage($storage);
		$this->setFormat($format);
		
		$modelList = $this->_listModelIds();

		foreach ($modelList as $modelId) {
			$this[$modelId] = new Garp_Spawn_Config_Model_Base($modelId, $storage, $format);
		}
	}
	
	/**
	 * Returns the storage interface
	 */
	public function getStorage() {
		return $this->_storage;
	}
	
	public function setStorage(Garp_Spawn_Config_Storage_Interface $storage) {
		$this->_storage = $storage;
		return $this;
	}
	
	public function setFormat(Garp_Spawn_Config_Format_Interface $format) {
		$this->_format = $format;
		return $this;
	}
	
	/**
	 * @return	Garp_Spawn_Config_Storage_Interface
	 */
	protected function _getDefaultStorage() {
		$configDir = APPLICATION_PATH . self::DEFAULT_CONFIG_PATH;
		$extension = self::DEFAULT_EXTENSION;
		
		return new Garp_Spawn_Config_Storage_File($configDir, $extension);
	}
	
	/**
	 * @return	Garp_Spawn_Config_Format_Interface
	 */
	protected function _getDefaultFormat() {
		return new Garp_Spawn_Config_Format_Json();
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