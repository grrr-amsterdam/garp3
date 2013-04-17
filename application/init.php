<?php
/**
* 	Before inclusion of this file:
*	
* 	APPLICATION_ENV needs to be defined.
* 	
* 	optional:	
* 	bool READ_FROM_CACHE, default true
* 	string MEMCACHE_HOST, default '127.0.0.1'
* 	
*/
define('APPLICATION_PATH', BASE_PATH . '/application');
define('GARP_APPLICATION_PATH', BASE_PATH . '/garp/application');
defined('READ_FROM_CACHE') || define('READ_FROM_CACHE', true);
defined('MEMCACHE_HOST') || define('MEMCACHE_HOST', '127.0.0.1');

$isCli = false;
if (
	array_key_exists('HTTP_HOST', $_SERVER) &&
	$_SERVER['HTTP_HOST']
) {
	//	h t t p   c o n t e x t
	define('HTTP_HOST', $_SERVER['HTTP_HOST']);

	set_include_path(
		realpath(APPLICATION_PATH.'/../library')
		. PATH_SEPARATOR . '.'
	);

} else {
	//	c l i   c o n t e x t
	define('HTTP_HOST', gethostname());

	set_include_path(
		'.'
		. PATH_SEPARATOR . BASE_PATH . '/library'
		. PATH_SEPARATOR . get_include_path()
	);

	$isCli = true;
}

@include_once(APPLICATION_PATH.'/configs/version.php');
if (!defined('APP_VERSION')) {
	define('APP_VERSION', 1);
}
@include_once(APPLICATION_PATH.'/../garp/application/configs/version.php');
if (!defined('GARP_VERSION')) {
	define('GARP_VERSION', 1);
}


require 'Garp/Loader.php';

/**
 * Set up class loading.
 */ 
$classLoader = Garp_Loader::getInstance(array(
	'paths' => array(
		array(
			'namespace' => '*',
			'path' => realpath(APPLICATION_PATH.'/../library')
		),
		array(
			'namespace' => 'Model',
			'path' => APPLICATION_PATH.'/modules/default/models/',
			'ignore' => 'Model_'
		),
		array(
			'namespace' => 'G_Model',
			'path' => GARP_APPLICATION_PATH.'/modules/g/models/',
			'ignore' => 'G_Model_'
		),
		array(
			'namespace' => 'Mocks_Model',
			'path' => GARP_APPLICATION_PATH.'/modules/mocks/models/',
			'ignore' => 'Mocks_Model_'
		)
	)
));
$classLoader->register();

/**
 * Save wether we are in a cli context
 */
Zend_Registry::set('CLI', $isCli);

/**
 * Set up caching
*/
$rootFolder = strtolower(dirname(APPLICATION_PATH.'/'));
$rootFolder = str_replace(DIRECTORY_SEPARATOR, '_', $rootFolder);
$filePrefix = $rootFolder.'_'.APPLICATION_ENV;
$filePrefix = preg_replace('/[^a-zA-A0-9_]/', '_', $filePrefix).'_';

$frontendName = 'Core';

if (!extension_loaded('memcache')) {
	$backendName = 'BlackHole';
	$cacheStoreEnabled = false;
} else {
	$backendName = 'Memcached';
	$cacheStoreEnabled = true;	
}

$frontendOptions = array(
	// for debug purposes; quickly turn off caching here
	'caching' => $cacheStoreEnabled,
	'lifetime' => 7200,
	'cache_id_prefix' => $filePrefix,
	'servers' => array(
		array(
			'host' => MEMCACHE_HOST,
			'port' => '11211'
		)
	),
	// slightly slower, but necessary when caching arrays or objects (like query results)
	'automatic_serialization' => true,
);
$backendOptions = array(
	'cache_dir' => APPLICATION_PATH.'/data/cache',
	// include the hostname and app environment in the filename for security
	'file_name_prefix' => $filePrefix,
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


/**
 * Developer convenience methods.
 * NOTE: this area should be used for adding easy shortcut methods a developer 
 * may use. Real implementation code is probably best fitted in its own class,
 * such as controllers, models, behaviors, or helpers.
 */

/**
 * Shortcut to logging messages.
 * @param String $file Basename of a log file. Extension may be omitted. 
 * 					   File will end up in /application/data/logs
 * @param String $message Your log message. Arrays will be print_r'd.
 * @param Int $priority A Zend_Log priority (e.g. INFO, NOTICE, WARN etc.)
 * @return Void
 */
function dump($file, $message, $priority = Zend_Log::INFO) {
	if (strpos($file, '.') === false) {
		$file .= '.log';
	}

	$stream = fopen(APPLICATION_PATH.'/data/logs/'.$file, 'a');
	$writer = new Zend_Log_Writer_Stream($stream);
	$logger = new Zend_Log($writer);
	$message = is_array($message) ? print_r($message, true) : $message;
	$logger->log($message, $priority);
}


/**
 * Translate text
 * @param String $str
 * @return String
 */
function __($str) {
	$translate = Zend_Registry::get('Zend_Translate');
	return $translate->_($str);
}
