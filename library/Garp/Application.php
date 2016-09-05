<?php
/**
 * Garp_Application
 * Provides the extra functionality of being able to cache config files.
 *
 * @package Garp
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Application extends Zend_Application {
    const UNDERCONSTRUCTION_LOCKFILE = 'underconstruction.lock';

    protected $_configFile;

    /**
     * Constructor
     *
     * Initialize application. Potentially initializes include_paths, PHP
     * settings, and bootstrap class.
     *
     * Overwritten to save a reference to the original config file
     *
     * @param  string                   $environment
     * @param  string|array|Zend_Config $options String path to configuration file,
     *                                           or array/Zend_Config of configuration options
     * @param bool $suppressNotFoundWarnings Should warnings be suppressed when a file
     *                                       is not found during autoloading?
     * @throws Zend_Application_Exception When invalid options are provided
     * @return void
     */
    public function __construct($environment, $options = null, $suppressNotFoundWarnings = null) {
        $this->_environment = (string) $environment;

        $this->_autoloader = Zend_Loader_Autoloader::getInstance();
        $this->_autoloader->suppressNotFoundWarnings($suppressNotFoundWarnings);

        if (null !== $options) {
            if (is_string($options)) {
                $this->_configFile = $options;
                $options = $this->_loadConfig($options);
            } elseif ($options instanceof Zend_Config) {
                $options = $options->toArray();
            } elseif (!is_array($options)) {
                throw new Zend_Application_Exception(
                    'Invalid options provided; must be location of config file,'
                    . ' a config object, or an array'
                );
            }

            $this->setOptions($options);
        }
    }

    /**
     * Load configuration file of options.
     *
     * Optionally will cache the configuration.
     *
     * @param  string $file
     * @throws Zend_Application_Exception When invalid configuration file is provided
     * @return array
     */
    protected function _loadConfig($file) {
        $suffix = pathinfo($file, PATHINFO_EXTENSION);
        $suffix = ($suffix === 'dist') ?
                    pathinfo(basename($file, ".$suffix"), PATHINFO_EXTENSION) : $suffix;
        if ($suffix == 'ini') {
            $config = Garp_Config_Ini::getCached($file, $this->getEnvironment())->toArray();
        } else {
            $config = parent::_loadConfig($file);
        }
        return $config;
    }

    public function bootstrap($resource = null) {
        Zend_Registry::set('config', new Zend_Config($this->getOptions()));
        return parent::bootstrap();
    }

    public static function isUnderConstruction() {
        return file_exists(self::getUnderConstructionLockFilePath());
    }

    public static function getUnderConstructionLockFilePath() {
        return APPLICATION_PATH . '/../' . self::UNDERCONSTRUCTION_LOCKFILE;
    }

    public static function setUnderConstruction($enabled) {
        $lockFilePath = static::getUnderConstructionLockFilePath();
        return $enabled ?
            touch($lockFilePath) :
            !file_exists($lockFilePath) || unlink($lockFilePath);
    }

    public function getConfigFile() {
        return $this->_configFile;
    }
}
