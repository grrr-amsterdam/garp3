<?php
/**
 * Before inclusion of this file:
 *
 *   APPLICATION_ENV needs to be defined.
 *
 * Optionally you may define:
 *   bool READ_FROM_CACHE, default true
 *   string MEMCACHE_HOST, default '127.0.0.1'
 *   string SENTRY_API_URL
 *   string BASE_PATH
 *
 * @package Garp
 * @author  Harmen Janssen <harmen@grrr.nl>
 * @author  David Spreekmeester <david@grrr.nl>
 */
if (!defined('BASE_PATH')) {
    define('BASE_PATH', realpath(dirname(__FILE__) . '/../..'));
}

define('APPLICATION_PATH', BASE_PATH . '/application');
define('GARP_APPLICATION_PATH', realpath(dirname(__FILE__)));

// Sentry integration
if (defined('SENTRY_API_URL') && APPLICATION_ENV !== 'development') {
    $ravenClient = new Raven_Client(SENTRY_API_URL);
    $ravenErrorHandler = new Raven_ErrorHandler($ravenClient);
    $ravenErrorHandler->registerExceptionHandler();
    $ravenErrorHandler->registerErrorHandler();
    $ravenErrorHandler->registerShutdownFunction();
    Zend_Registry::set('RavenClient', $ravenClient);
}

if (file_exists(APPLICATION_PATH . '/../.env')) {
    $dotenv = new Dotenv\Dotenv(APPLICATION_PATH . '/..');
    $dotenv->load();
}

$appSpecificInit = APPLICATION_PATH . '/configs/init.php';
if (file_exists($appSpecificInit)) {
    include_once $appSpecificInit;
}

defined('READ_FROM_CACHE') || define('READ_FROM_CACHE', true);
defined('MEMCACHE_HOST') || define('MEMCACHE_HOST', '127.0.0.1');
defined('MEMCACHE_PORT') || define('MEMCACHE_PORT', '11211');

$isCli = false;
if (array_key_exists('HTTP_HOST', $_SERVER) && $_SERVER['HTTP_HOST']) {
    //  h t t p   c o n t e x t
    define('HTTP_HOST', $_SERVER['HTTP_HOST']);
} else {
    //  c l i   c o n t e x t
    define('HTTP_HOST', gethostname());
    $isCli = true;
}

if (!$isCli && Garp_Application::isUnderConstruction()) {
    //header('HTTP/1.1 503 Service Temporarily Unavailable');
    //header('Retry-After: ' . date(DateTime::RFC2822, strtotime('+5 minutes')));
    header('Cache-Control: no-cache');
    header('Pragma: no-cache');
    header('Expires: ' . date(DATE_RFC1123, strtotime('-1 year')));

    include GARP_APPLICATION_PATH . '/modules/g/views/scripts/under-construction.phtml';
    // @codingStandardsIgnoreStart
    exit;
    // @codingStandardsIgnoreEnd
}

/**
 * Save wether we are in a cli context
 */
Zend_Registry::set('CLI', $isCli);

/**
 * Set up caching
 */
$rootFolder = strtolower(dirname(APPLICATION_PATH . '/'));
$rootFolder = str_replace(DIRECTORY_SEPARATOR, '_', $rootFolder);
$filePrefix = $rootFolder . '_' . APPLICATION_ENV;
$filePrefix = preg_replace('/[^a-zA-A0-9_]/', '_', $filePrefix) . '_';

$frontendName = 'Core';
$memcacheIsConfigured = MEMCACHE_PORT && MEMCACHE_HOST;
$memcacheAvailable = extension_loaded('memcache');
if ($memcacheAvailable) {
    // Attempt a connection to memcache, to see if we can use it
    $memcache = new Memcache;
    $memcacheAvailable = @$memcache->connect(MEMCACHE_HOST, MEMCACHE_PORT);
}

// Only use 'Memcached' if we can reasonably connect. Otherwise it's not worth it to crash on,
// we can just fall back to BlackHole
if ($memcacheIsConfigured && $memcacheAvailable) {
    $backendName       = 'Memcached';
    $cacheStoreEnabled = true;
    $useWriteControl   = true;
} else {
    $backendName       = 'Black-Hole';
    $cacheStoreEnabled = false;
    $useWriteControl   = false;
}

$frontendOptions = array(
    // for debug purposes; quickly turn off caching here
    'caching' => $cacheStoreEnabled,
    'lifetime' => 7200,
    'cache_id_prefix' => $filePrefix,
    // slightly slower, but necessary when caching arrays or objects (like query results)
    'automatic_serialization' => true,
    'write_control' => $useWriteControl,
);
$backendOptions = array(
    'cache_dir' => APPLICATION_PATH . '/data/cache',
    // include the hostname and app environment in the filename for security
    'file_name_prefix' => $filePrefix,
    'servers' => array(
        array(
            'host' => MEMCACHE_HOST,
            'port' => MEMCACHE_PORT
        )
    ),
);

$cache = Zend_Cache::factory(
    $frontendName,
    $backendName,
    $frontendOptions,
    $backendOptions
);

// Add default caching of metadata to models.
Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
/**
 * Use this control for toggling cache on and off. Do not use
 * the 'caching' option in the Zend_Cache configuration, because
 * that also disables cleaning the cache. In the case of the
 * admin pages this is unwanted behavior.
 */
Zend_Registry::set('readFromCache', READ_FROM_CACHE);
// Store the cache frontend in the registry for easy access
Zend_Registry::set('CacheFrontend', $cache);

require_once 'functions.php';
