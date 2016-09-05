<?php
/**
 * Garp_Config_Ini
 * Complement to Zend_Config_Ini.
 * Creates an ini file from a string instead of a file.
 * I've stolen some methods from Zend_Config_Ini for parsing ini-style configuration strings.
 *
 * @package Garp_Config
 * @author  David Spreekmeester <david@grrr.nl>
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Config_Ini extends Zend_Config_Ini {

    /**
     * Keep track of loaded ini files within a single request
     *
     * @var array
     */
    protected static $_store = array();

    /**
     * Receive a config ini file from cache
     *
     * @param string $filename
     * @param string $environment
     * @return Garp_Config_Ini
     */
    public static function getCached($filename, $environment = null) {
        $environment = $environment ?: APPLICATION_ENV;
        $cache = Zend_Registry::get('CacheFrontend');
        $key = preg_replace('/[^0-9a-zA-Z_]/', '_', basename($filename));
        $key .= '_' . $environment;

        // Check the store first to see if the ini file is already loaded within this session
        if (array_key_exists($key, static::$_store)) {
            return static::$_store[$key];
        }

        $config = $cache->load('Ini_Config_' . $key);
        if (!$config) {
            $config = new Garp_Config_Ini($filename, $environment);
            $cache->save($config, 'Ini_Config_' . $key);
        }
        static::$_store[$key] = $config;
        return $config;
    }

    public function __construct($filename, $section = null, $options = false) {
        $options['allowModifications'] = true;
        try {
            parent::__construct($filename, $section, $options);
        } catch (Zend_Config_Exception $e) {
            // Catch invalid Section exceptions here...
            if (!count(sscanf($e->getMessage(), "Section '%s' cannot be found in %s"))) {
                throw $e;
            }
            // ...and provide a better one we can use to show a friendly error
            $validSections = array_keys($this->_loadIniFile($filename));
            $superException = new Garp_Config_Ini_InvalidSectionException($e->getMessage());
            $superException->setValidSections($validSections);
            throw $superException;
        }

        if (isset($this->config)) {
            $this->_mergeSubConfigs($section, $options);
        }
    }

    protected function _mergeSubConfigs($section, $options) {
        foreach ($this->config as $subConfigPath) {
            $subConfig = new Garp_Config_Ini($subConfigPath, $section, $options);
            $this->merge($subConfig);
        }
    }

    /**
     * Hacked to allow ini strings as well as ini files.
     *
     * @param string|Garp_Config_Ini_String $filename If this is a Garp_Config_Ini_String, an ini
     *                                                string is assumed instead of an ini file.
     * @return array
     */
    protected function _parseIniFile($filename) {
        if ($filename instanceof Garp_Config_Ini_String) {
            $ini = $filename->getValue();
            return parse_ini_string($ini);
        }
        return parent::_parseIniFile($filename);
    }

    /**
     * Take an ini string to populate the config object.
     *
     * @param string $iniString
     * @param string $section
     * @param array $options
     * @return Garp_Config_Ini
     * @see Zend_Config_Ini::__construct for an explanation of the second and third arguments.
     */
    public static function fromString($iniString, $section = null, $options = false) {
        return new Garp_Config_Ini(new Garp_Config_Ini_String($iniString), $section, $options);
    }
}

