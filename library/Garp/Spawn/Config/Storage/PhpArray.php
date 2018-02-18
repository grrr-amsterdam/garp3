<?php
/**
 * @package Garp_Spawn_Config_Storage
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Spawn_Config_Storage_PhpArray implements Garp_Spawn_Config_Storage_Interface {
    /**
     * The provided configuration array
     *
     * @var array
     */
    protected $_config;

    /**
     * @param  array $config Associative array with model IDs as keys, and the model configuration as their value.
     * @return void
     */
    public function __construct(array $config) {
        if (!$config) {
            throw new Exception('The configuration array is empty.');
        }
        $this->_config = $config;
    }

    /**
     * Returns the configuration data for the provided object ID.
     *
     * @param  string $objectId The object ID.
     * @return array            The configuration content
     */
    public function load(string $objectId) {
        if (!array_key_exists($objectId, $this->_config)) {
            throw new Exception("Sorry, could not find the config for {$objectId} in the PhpArray.");
        }
        return $this->_config[$objectId];
    }

    /**
     * Returns a list of IDs referencing the configured objects
     *
     * @return array An array of strings, containing object ids.
     */
    public function listObjectIds(): array {
        return array_keys($this->_config);
    }

}
