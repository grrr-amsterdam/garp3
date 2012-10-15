<?php
/**
 * @author David Spreekmeester | Grrr.nl
 * 
 */
interface Garp_Model_Spawn_Config_Storage_Interface {
	/** Returns the configuration data for the provided object ID. */
	public function load($objectId);


	/**
	 * Returns a list of IDs referencing the configured objects
	 * @return Array An array of strings, containing object ids.
	 */
	public function listObjectIds();
}