<?php
/**
 * @package Garp_Spawn_Config_Storage
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Spawn_Config_Storage_File implements Garp_Spawn_Config_Storage_Interface {
    protected $_directory;
    protected $_extension;

    public function __construct($directory, $extension) {
        $this->_directory = $this->_addTrailingSlash($directory);
        $this->_extension = $extension;
    }

    /**
     * Returns the configuration data for the provided object ID.
     *
     * @param  string $objectId The object ID.
     * @return array            The configuration content
     */
    public function load(string $objectId) {
        $path = $this->_getPath($objectId);
        $this->_validatePath($path);

        $contents = file_get_contents($path);

        if (!strlen($contents)) {
            throw new Exception("The configuration file is empty. Fill it at " . $path);
        }

        return $contents;
    }

    /**
     * Returns a list of IDs referencing the configured objects
     *
     * @return array An array of strings, containing object ids.
     */
    public function listObjectIds(): array {
        $modelNames   = array();
        $suffixLength = strlen($this->_extension) + 1;
        $filePattern  = '*.' . $this->_extension;
        $filenames    = glob($this->_directory . $filePattern);

        foreach ($filenames as $filename) {
            $modelNames[] = substr(basename($filename), 0, -$suffixLength);
        }

        return $modelNames;
    }

    protected function _addTrailingSlash($directory) {
        return $directory . ($directory[strlen($directory) - 1] === '/' ? '' : '/');
    }

    protected function _getPath($objectId) {
        return $this->_directory . $objectId . '.' . $this->_extension;
    }

    protected function _validatePath($path) {
        if (!is_file($path)) {
            throw new Exception("The provided path ({$path}) is not a file.");
        }

        if (!file_exists($path)) {
            throw new Exception("The configuration file does not exist yet. Create one at " . $path);
        }

        $suffixLength = strlen($this->_extension) + 1;
        $incorrectExtension = $suffixLength
            && substr(basename($path), -$suffixLength, $suffixLength) !== ('.' . $this->_extension);

        if ($incorrectExtension) {
            throw new Exception("The configuration file does not have the proper extension. It should end in '.{$this->_extension}'.");
        }
    }
}
