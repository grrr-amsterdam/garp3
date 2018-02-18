<?php
/**
 * @package Garp_Spawn_Config_Storage
 * @author  David Spreekmeester <david@grrr.nl>
 */
interface Garp_Spawn_Config_Storage_Interface {
    /**
     * Returns the configuration data for the provided object ID.
     *
     * @param  string $objectId The object ID.
     * @return array            The configuration content
     */
    public function load(string $objectId);

    /**
     * Returns a list of IDs referencing the configured objects
     *
     * @return array An array of strings, containing object ids.
     */
    public function listObjectIds(): array;
}
