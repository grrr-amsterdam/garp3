<?php
/**
 * Garp_Loader_PluginLoader
 * Overwrites Zend_Loader_PluginLoader to add some pluginLoaderCache tweaks
 *
 * @author       Harmen Janssen | grrr.nl
 * @package      Garp
 * @subpackage   Loader
 */
class Garp_Loader_PluginLoader extends Zend_Loader_PluginLoader {
	/**
     * Load a plugin via the name provided
     *
     * ///// GARP customization /////
     * This method is copied from Zend_Loader_PluginLoader in order to make the 
     * self keyword point to this class instead of Zend_Loader_PluginLoader.
     *
     * @param  string $name
     * @param  bool $throwExceptions Whether or not to throw exceptions if the
     * class is not resolved
     * @return string|false Class name of loaded class; false if $throwExceptions
     * if false and no class found
     * @throws Zend_Loader_Exception if class not found
     */
    public function load($name, $throwExceptions = true)
    {
        $name = $this->_formatName($name);
        if ($this->isLoaded($name)) {
            return $this->getClassName($name);
        }

        if ($this->_useStaticRegistry) {
            $registry = self::$_staticPrefixToPaths[$this->_useStaticRegistry];
        } else {
            $registry = $this->_prefixToPaths;
        }

        $registry  = array_reverse($registry, true);
        $found     = false;
        $classFile = str_replace('_', DIRECTORY_SEPARATOR, $name) . '.php';
        $incFile   = self::getIncludeFileCache();
        foreach ($registry as $prefix => $paths) {
            $className = $prefix . $name;

            if (class_exists($className, false)) {
                $found = true;
                break;
            }

            $paths     = array_reverse($paths, true);

            foreach ($paths as $path) {
                $loadFile = $path . $classFile;
                if (Zend_Loader::isReadable($loadFile)) {
                    include_once $loadFile;
                    if (class_exists($className, false)) {
                        if (null !== $incFile) {
                            self::_appendIncFile($loadFile);
                        }
                        $found = true;
                        break 2;
                    }
                }
            }
        }

        if (!$found) {
            if (!$throwExceptions) {
                return false;
            }

            $message = "Plugin by name '$name' was not found in the registry; used paths:";
            foreach ($registry as $prefix => $paths) {
                $message .= "\n$prefix: " . implode(PATH_SEPARATOR, $paths);
            }
            require_once 'Zend/Loader/PluginLoader/Exception.php';
            throw new Zend_Loader_PluginLoader_Exception($message);
       }

        if ($this->_useStaticRegistry) {
            self::$_staticLoadedPlugins[$this->_useStaticRegistry][$name]     = $className;
        } else {
            $this->_loadedPlugins[$name]     = $className;
        }
        return $className;
    }

	/**
     * Append an include_once statement to the class file cache
     *
     * ///// GARP customization /////
     * Contains various tweaks that make a developer's life better.
     *
     * @param  string $incFile
     * @return void
     */
    protected static function _appendIncFile($incFile) {
		$line = "<?php include_once '$incFile'; ?>\n";
		file_put_contents(self::$_includeFileCache, $line, FILE_APPEND | LOCK_EX);
    }
}
