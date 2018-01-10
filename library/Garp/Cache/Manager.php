<?php
use Garp\Functional as f;
/**
 * Garp_Cache_Manager
 * Provides various ways of purging the many caches Garp uses.
 *
 * @package Garp_Cache
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Cache_Manager {
    /**
     * Read query cache
     *
     * @param Garp_Model_Db $model
     * @param String $key
     * @return Mixed
     */
    public static function readQueryCache(Garp_Model_Db $model, $key) {
        $cache = new Garp_Cache_Store_Versioned($model->getName() . '_version');
        return $cache->read($key);
    }

    /**
     * Write query cache
     *
     * @param Garp_Model_Db $model
     * @param String $key
     * @param Mixed $results
     * @return Void
     */
    public static function writeQueryCache(Garp_Model_Db $model, $key, $results) {
        $cache = new Garp_Cache_Store_Versioned($model->getName() . '_version');
        $cache->write($key, $results);
    }

    /**
     * Purge all cache system wide
     *
     * @param Array|Garp_Model_Db $tags
     * @param Boolean $createClusterJob Whether this purge should create a job to clear the other
     *                                  nodes in this server cluster, if applicable.
     * @param String $cacheDir Directory which stores static HTML cache files.
     * @return Void
     */
    public static function purge($tags = array(), $createClusterJob = true, $cacheDir = false) {
        if ($tags instanceof Garp_Model_Db) {
            $tags = self::getTagsFromModel($tags);
        }

        self::purgeStaticCache($tags, $cacheDir);
        self::purgeMemcachedCache($tags);
        self::purgeOpCache();

        $ini = Zend_Registry::get('config');
        if ($createClusterJob && $ini->app->clusteredHosting) {
            Garp_Cache_Store_Cluster::createJob($tags);
        }
    }

    /**
     * Clear the Memcached cache that stores queries and ini files and whatnot.
     *
     * @param Array|Garp_Model_Db $modelNames
     * @return Void
     */
    public static function purgeMemcachedCache($modelNames = array()) {
        if (!Zend_Registry::isRegistered('CacheFrontend')) {
            return;
        }

        if (!Zend_Registry::get('CacheFrontend')->getOption('caching')) {
            // caching is disabled
            return;
        }

        if ($modelNames instanceof Garp_Model_Db) {
            $modelNames = self::getTagsFromModel($modelNames);
        }

        if (empty($modelNames)) {
            $cacheFront = Zend_Registry::get('CacheFrontend');
            return $cacheFront->clean(Zend_Cache::CLEANING_MODE_ALL);
        }
        foreach ($modelNames as $modelName) {
            $model = new $modelName();
            self::_incrementMemcacheVersion($model);
            if (!$model->getObserver('Translatable')) {
                continue;
            }
            // Make sure cache is cleared for all languages.
            $locales = Garp_I18n::getLocales();
            foreach ($locales as $locale) {
                try {
                    $modelFactory = new Garp_I18n_ModelFactory($locale);
                    $i18nModel = $modelFactory->getModel($model);
                    self::_incrementMemcacheVersion($i18nModel);
                } catch (Garp_I18n_ModelFactory_Exception_ModelAlreadyLocalized $e) {
                    // all good in the hood  ｡^‿^｡
                }
            }
        }
    }

    /**
     * This clears Zend's Static caching.
     *
     * @param Array|Garp_Model_Db $modelNames Clear the cache of a specific bunch of models.
     * @param String $cacheDir Directory containing the cache files
     * @return Void
     */
    public static function purgeStaticCache($modelNames = array(), $cacheDir = false) {
        if (!Zend_Registry::get('CacheFrontend')->getOption('caching')) {
            // caching is disabled (yes, this particular frontend is not in charge of static
            // cache, but in practice this toggle is used to enable/disable caching globally, so
            // this matches developer expectations better, probably)
            return;
        }

        if ($modelNames instanceof Garp_Model_Db) {
            $modelNames = self::getTagsFromModel($modelNames);
        }

        $cacheDir = $cacheDir ?: self::_getStaticCacheDir();
        if (!$cacheDir) {
            return;
        }
        $cacheDir = str_replace(' ', '\ ', $cacheDir);
        $cacheDir = rtrim($cacheDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        // Destroy all if no model names are given
        if (empty($modelNames)) {
            $allPath = $cacheDir . '*';
            return self::_deleteStaticCacheFile($allPath);
        }
        // Fetch model names from configuration
        if (!$tagList = self::_getTagList()) {
            return;
        }
        $_purged = array();
        foreach ($modelNames as $tag) {
            if (!$tagList->{$tag}) {
                continue;
            }
            foreach ($tagList->{$tag} as $path) {
                while (strpos($path, '..') !== false) {
                    $path = str_replace('..', '.', $path);
                }
                $filePath = $cacheDir . $path;
                // Keep track of purged paths, forget about duplicates
                if (in_array($filePath, $_purged)) {
                    continue;
                }
                self::_deleteStaticCacheFile($filePath);
                $_purged[] = $filePath;
            }
        }
    }

    /**
     * This clears the OpCache. This reset can only be done with an http request.
     *
     * @return Void
     */
    public static function purgeOpCache() {
        // get deployment file
        if (APPLICATION_ENV === 'development' || APPLICATION_ENV === 'testing') {
            return;
        }

        $hostName = Zend_Registry::get('config')->app->domain;
        foreach (self::_getServerNames() as $serverName) {
            self::_resetOpcache($serverName, $hostName);
        }
    }

    protected static function _getServerNames() {
        $deployConfig = new Garp_Deploy_Config;
        $params = $deployConfig->getParams(APPLICATION_ENV);

        return f\map(
            f\prop('server'),
            $params['server']
        );
    }

    protected static function _resetOpcache($serverName, $hostName) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, "{$serverName}/g/content/opcachereset");
        curl_setopt(
            $ch, CURLOPT_HTTPHEADER, array('Host: ' . $hostName)
        );

        curl_exec($ch);

        curl_close($ch);
    }

    /**
     * Remove static cache file(s)
     *
     * @param string $path
     * @return bool
     */
    protected static function _deleteStaticCacheFile($path) {
        $success = @system('rm -rf ' . $path . ';') !== false;
        return $success;
    }

    /**
     * Schedule a cache clear in the future.
     *
     * @param Int $timestamp
     * @param Array $tags
     * @see Garp_Model_Behavior_Draftable for a likely pairing
     * @return Bool A guesstimate of wether the command has successfully been scheduled.
     *              Note that it's hard to determine if this is true. I have, for instance,
     *              not yet found a way to determine if the atrun daemon actually is active.
     */
    public static function scheduleClear($timestamp, array $tags = array()) {
        // Use ScheduledJob model if available, otherwise fall back to `at`
        if (!class_exists('Model_ScheduledJob')) {
            return static::createAtCommand($timestamp, $tags);
        }
        return static::createScheduledJob($timestamp, $tags);
    }

    public static function createScheduledJob($timestamp, array $tags = array()) {
        $cmd = 'Cache clear';
        if (count($tags)) {
            $cmd .= ' ' . implode(' ', $tags);
        }
        $scheduledJobModel = new Model_ScheduledJob();
        return $scheduledJobModel->insert(
            array(
                'command' => $cmd,
                'at' => date('Y-m-d H:i:s', $timestamp),
            )
        );
    }

    /**
     * Ye olde scheduleClear()
     *
     * @param int $timestamp
     * @param array $tags
     * @deprecated More or less. You can use it, but ScheduledJob does it better
     * @return bool
     */
    public static function createAtCommand($timestamp, array $tags = array()) {
        $time = date('H:i d.m.y', $timestamp);

        // Sanity check: are php and at available?
        // ('which' returns an empty string in case of failure)
        if (!exec('which php') || !exec('which at')) {
            throw new Garp_Model_Behavior_Exception(
                'php and/or at are not available in this shell.'
            );
        }
        // The command must come from a file, create that in the data folder of this project.
        // Add timestamp to the filename so we can safely delete the file later
        $tags = implode(' ', $tags);
        $file = APPLICATION_PATH . '/data/at_cmd_' . time() . md5($tags);

        $garpScriptFile = self::_getGarpCliScriptPath();
        $cmd  = 'php ' . $garpScriptFile . ' Cache clear --APPLICATION_ENV=' . APPLICATION_ENV .
            ' ' . $tags . ';';

        // Create temp file
        $tmpFile = tmpfile();
        // Write temp file
        fwrite($tmpFile, $cmd);
        // Get path to temp file
        $tempFileMeta = stream_get_meta_data($tmpFile);
        $tempFilePath = $tempFileMeta['uri'];
        $atCmd = "at -f {$tempFilePath} {$time}";
        exec($atCmd);
        // @todo Actually evaluate the status of the command.
        // This returning true is a bit arbitrary.. I have actually not found a way to read
        // the error messages returned from at.

        // Clean up the tmp file
        fclose($tmpFile);
        return true;
    }

    /**
     * Get cache tags used with a given model.
     *
     * @param Garp_Model_Db $model
     * @return Array
     */
    public static function getTagsFromModel(Garp_Model_Db $model) {
        $tags = array(get_class($model));
        foreach ($model->getBindableModels() as $modelName) {
            if (!in_array($modelName, $tags)) {
                $tags[] = $modelName;
            }
        }
        return $tags;
    }

    /**
     * Returns information for debugging purposes.
     *
     * @return Zend_Cache_Backend
     */
    public static function getCacheBackend() {
        if (Zend_Registry::isRegistered('CacheFrontend')) {
            $cacheFront = Zend_Registry::get('CacheFrontend');
            return $cacheFront->getBackend();
        }
        return null;
    }

    /**
     * Increment the version to invalidate a given model's cache.
     *
     * @param Garp_Model_Db $model
     * @return Void
     */
    protected static function _incrementMemcacheVersion(Garp_Model_Db $model) {
        $cache = new Garp_Cache_Store_Versioned($model->getName() . '_version');
        $cache->incrementVersion();
    }

    /**
     * Fetch the cache directory for static caching
     *
     * @return String
     */
    protected static function _getStaticCacheDir() {
        $config = Zend_Registry::get('config');
        if (!isset($config->resources->cacheManager->page->backend->options->public_dir)) {
            return false;
        }
        return $config->resources->cacheManager->page->backend->options->public_dir;

        /**
         * Cache dir used to be read from the Front controller, which I would prefer under normal
         * circumstances. But the front controller is not bootstrapped in CLI environment, so I've
         * refactored to read from cache. I'm leaving this for future me to think about some more
         *
         * (full disclaimer: I'm probably never going to think about it anymore)
         *
        $front = Zend_Controller_Front::getInstance();
        if (!$front->getParam('bootstrap')
            || !$front->getParam('bootstrap')->getResource('cachemanager')) {
            Garp_Cli::errorOut('no bootstrap or whatever');
            return false;
        }
        $cacheManager = $front->getParam('bootstrap')->getResource('cachemanager');
        $cache = $cacheManager->getCache(Zend_Cache_Manager::PAGECACHE);
        $cacheDir = $cache->getBackend()->getOption('public_dir');
        return $cacheDir;
         */
    }

    /**
     * Fetch mapping of available tags to file paths
     *
     * @return Zend_Config
     */
    protected static function _getTagList() {
        $config = Zend_Registry::get('config');
        if (!empty($config->staticcaching->tags)) {
            return $config->staticcaching->tags;
        }

        // For backward-compatibility: fall back to a separate cache.ini
        $ini = new Garp_Config_Ini(APPLICATION_PATH . '/configs/cache.ini', APPLICATION_ENV);
        if (!empty($ini->tags)) {
            return $ini->tags;
        }

        return null;
    }

    protected static function _getGarpCliScriptPath() {
        if (strpos(APPLICATION_PATH, 'releases') === false) {
            return realpath(GARP_APPLICATION_PATH . '/../scripts/garp.php');
        }
        // When `releases` is in the path, assume Capistrano setup and point to the `current`
        // symlink. The different release folders are purged over time, so not reliable to use when
        // scheduling a future operation.
        return realpath(APPLICATION_PATH . '/../../..') .
            '/current/vendor/grrr-amsterdam/garp3/scripts/garp.php';
    }

}
