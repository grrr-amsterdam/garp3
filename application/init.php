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

if (file_exists(APPLICATION_PATH . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createUnsafeImmutable(APPLICATION_PATH . '/..');
    $dotenv->load();
}

// Sentry integration
if (getenv('SENTRY_API_URL') || (defined('SENTRY_API_URL'))) {
    $sentryApiUrl = getenv('SENTRY_API_URL') ?: SENTRY_API_URL;
    \Sentry\init([
        'dsn' => $sentryApiUrl,
        'release' => strval(new Garp_Version),
        'environment' => APPLICATION_ENV,
        'capture_silenced_errors' => true,
    ]);
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
    //  HTTP context
    define('HTTP_HOST', $_SERVER['HTTP_HOST']);
} else {
    //  CLI context
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
$memcacheIsConfigured = MEMCACHE_HOST && MEMCACHE_PORT;

// Memcached (with 'memcache' lib, used in PHP 5.6 and lower)
$memcacheAvailable = extension_loaded('memcache');
if ($memcacheIsConfigured && $memcacheAvailable) {
    // Attempt a connection to the memcached server, to see if we can use it
    $memcache = new Memcache;
    $memcacheAvailable = @$memcache->connect(MEMCACHE_HOST, MEMCACHE_PORT);
}

// Memcached (with 'memcached/libmemcached' lib, used in PHP 7.0+)
$memcachedAvailable = extension_loaded('memcached');
if ($memcacheIsConfigured && $memcachedAvailable) {
    // Attempt a connection to the memcached server, to see if we can use it
    $memcached = new Memcached;
    $memcached->addServer(MEMCACHE_HOST, MEMCACHE_PORT);
    $memcachedAvailable = @$memcached->getVersion();
}

// Only use 'Memcached' or 'Libmemcached' if we can reasonably connect.
// Otherwise it's not worth it to crash on, we can just fall back to BlackHole.
if ($memcacheIsConfigured && $memcachedAvailable) {
    $backendName       = 'Libmemcached';
    $cacheStoreEnabled = true;
    $useWriteControl   = true;
} elseif ($memcacheIsConfigured && $memcacheAvailable) {
    $backendName       = 'Memcached';
    $cacheStoreEnabled = true;
    $useWriteControl   = true;
} else {
    $backendName       = 'Black-Hole';
    $cacheStoreEnabled = true;
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

