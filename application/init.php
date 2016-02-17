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
if (!defined('BASE_PATH')) {
	define('BASE_PATH', realpath(dirname(__FILE__) . '/../..'));
}
define('APPLICATION_PATH', BASE_PATH . '/application');
define('GARP_APPLICATION_PATH', BASE_PATH . '/garp/application');

$appSpecificInit = APPLICATION_PATH . '/configs/init.php';
if (file_exists($appSpecificInit)) {
	include_once($appSpecificInit);
}

defined('READ_FROM_CACHE') || define('READ_FROM_CACHE', true);
defined('MEMCACHE_HOST') || define('MEMCACHE_HOST', '127.0.0.1');
defined('MEMCACHE_PORT') || define('MEMCACHE_PORT', '11211');

$isCli = false;
if (
	array_key_exists('HTTP_HOST', $_SERVER) &&
	$_SERVER['HTTP_HOST']
) {
	//	h t t p   c o n t e x t
	define('HTTP_HOST', $_SERVER['HTTP_HOST']);

	set_include_path(
		realpath(APPLICATION_PATH.'/../library')
		. PATH_SEPARATOR . realpath(GARP_APPLICATION_PATH.'/../library')
		. PATH_SEPARATOR . '.'
	);

} else {
	//	c l i   c o n t e x t
	define('HTTP_HOST', gethostname());

	set_include_path(
		'.'
		. PATH_SEPARATOR . BASE_PATH . '/library'
		. PATH_SEPARATOR . realpath(GARP_APPLICATION_PATH.'/../library')
		. PATH_SEPARATOR . get_include_path()
	);

	$isCli = true;
}

if (!class_exists('Garp_Loader')) {
	require GARP_APPLICATION_PATH . '/../library/Garp/Loader.php';
}

/**
 * Set up class loading.
 */
$classLoader = Garp_Loader::getInstance()->addIncludePaths(array(
	array(
		'namespace' => '*',
		'path' => realpath(APPLICATION_PATH.'/../library')
	),
	array(
		'namespace' => 'Garp',
		'path' => realpath(GARP_APPLICATION_PATH.'/../library')
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
));
$classLoader->register();

if (!$isCli && Garp_Application::isUnderConstruction()) {
	header('HTTP/1.1 503 Service Temporarily Unavailable');
	header('Retry-After: ' . date(DateTime::RFC2822, strtotime('+5 minutes')));
	require(GARP_APPLICATION_PATH . '/modules/g/views/scripts/under-construction.phtml');
	exit;
}

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

$memcacheAvailable = extension_loaded('memcache');
if ($memcacheAvailable) {
	$memcache = new Memcache;
	$memcacheAvailable = @$memcache->connect(MEMCACHE_HOST, MEMCACHE_PORT);
}
if (!$memcacheAvailable) {
	$backendName       = 'Black-Hole';
	$cacheStoreEnabled = false;
	$useWriteControl   = false;
} else {
	$backendName       = 'Memcached';
	$cacheStoreEnabled = true;
	$useWriteControl   = true;
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
	'cache_dir' => APPLICATION_PATH.'/data/cache',
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

	$logger = Garp_Log::factory($file);
	$message = is_array($message) ? print_r($message, true) : $message;
	$logger->log($message, $priority);
}


/**
 * Translate text
 * @param String $str
 * @return String
 */
function __($str) {
	if (Zend_Registry::isRegistered('Zend_Translate')) {
		$translate = Zend_Registry::get('Zend_Translate');
		return call_user_func_array(array($translate, '_'), func_get_args());
	}
	return $str;
}

/**
 * Make the PHP language a little more expressive.
 * PHP 5.4 allows chaining of new instances like so;
 * (new Instance())->doSomething();
 * This method sort of brings this to earlier versions of PHP:
 * instance(new Instance())->doSomething();
 * @param Object $obj
 * @return Object
 */
function instance($obj) {
	if (is_string($obj)) {
		$obj = new $obj;
	}
	return $obj;
}

/**
 * Transform array of objects into a new array with just the given key of said objects
 */
function array_pluck($array, $column) {
	return array_map(function($obj) use ($column) {
		return isset($obj[$column]) ? $obj[$column] : null;
	}, $array);
}

/**
 * Flatten an array of arrays.
 * The cornerstone of functional programming.
 */
function concatAll($array) {
	$results = array();
	foreach ($array as $item) {
		// Merge arrays...
		if (is_array($item)) {
			$results = array_merge($results, $item);
			continue;
		}
		// ...push anything else.
		$results[] = $item;
	}
	return $results;
}

/**
 * Safe getter.
 * Returns the $default if the requested $key is not set.
 * Example:
 * $a = array('foo' => 123, 'bar' => 456);
 * array_get($a, 'foo'); // 123
 * array_get($a, 'baz'); // null
 * array_get($a, 'baz', 'abc'); // 'abc'
 */
function array_get(array $a, $key, $default = null) {
	return isset($a[$key]) ? $a[$key] : $default;
}

if (!function_exists('gzdecode')) {
	/**
 	 * @see http://nl1.php.net/gzdecode#82930
 	 */
	function gzdecode($data,&$filename='',&$error='',$maxlength=null) {
    	$len = strlen($data);
    	if ($len < 18 || strcmp(substr($data,0,2),"\x1f\x8b")) {
        	$error = "Not in GZIP format.";
        	return null;  // Not GZIP format (See RFC 1952)
    	}
    	$method = ord(substr($data,2,1));  // Compression method
    	$flags  = ord(substr($data,3,1));  // Flags
    	if ($flags & 31 != $flags) {
        	$error = "Reserved bits not allowed.";
        	return null;
    	}
    	// NOTE: $mtime may be negative (PHP integer limitations)
    	$mtime = unpack("V", substr($data,4,4));
    	$mtime = $mtime[1];
    	$xfl   = substr($data,8,1);
    	$os    = substr($data,8,1);
    	$headerlen = 10;
    	$extralen  = 0;
    	$extra     = "";
    	if ($flags & 4) {
        	// 2-byte length prefixed EXTRA data in header
        	if ($len - $headerlen - 2 < 8) {
            	return false;  // invalid
        	}
        	$extralen = unpack("v",substr($data,8,2));
        	$extralen = $extralen[1];
        	if ($len - $headerlen - 2 - $extralen < 8) {
            	return false;  // invalid
        	}
        	$extra = substr($data,10,$extralen);
        	$headerlen += 2 + $extralen;
    	}
    	$filenamelen = 0;
    	$filename = "";
    	if ($flags & 8) {
        	// C-style string
        	if ($len - $headerlen - 1 < 8) {
            	return false; // invalid
        	}
        	$filenamelen = strpos(substr($data,$headerlen),chr(0));
        	if ($filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8) {
            	return false; // invalid
        	}
        	$filename = substr($data,$headerlen,$filenamelen);
        	$headerlen += $filenamelen + 1;
    	}
    	$commentlen = 0;
    	$comment = "";
    	if ($flags & 16) {
        	// C-style string COMMENT data in header
        	if ($len - $headerlen - 1 < 8) {
            	return false;    // invalid
        	}
        	$commentlen = strpos(substr($data,$headerlen),chr(0));
        	if ($commentlen === false || $len - $headerlen - $commentlen - 1 < 8) {
            	return false;    // Invalid header format
        	}
        	$comment = substr($data,$headerlen,$commentlen);
        	$headerlen += $commentlen + 1;
    	}
    	$headercrc = "";
    	if ($flags & 2) {
        	// 2-bytes (lowest order) of CRC32 on header present
        	if ($len - $headerlen - 2 < 8) {
            	return false;    // invalid
        	}
        	$calccrc = crc32(substr($data,0,$headerlen)) & 0xffff;
        	$headercrc = unpack("v", substr($data,$headerlen,2));
        	$headercrc = $headercrc[1];
        	if ($headercrc != $calccrc) {
            	$error = "Header checksum failed.";
            	return false;    // Bad header CRC
        	}
        	$headerlen += 2;
    	}
    	// GZIP FOOTER
    	$datacrc = unpack("V",substr($data,-8,4));
    	$datacrc = sprintf('%u',$datacrc[1] & 0xFFFFFFFF);
    	$isize = unpack("V",substr($data,-4));
    	$isize = $isize[1];
    	// decompression:
    	$bodylen = $len-$headerlen-8;
    	if ($bodylen < 1) {
        	// IMPLEMENTATION BUG!
        	return null;
    	}
    	$body = substr($data,$headerlen,$bodylen);
    	$data = "";
    	if ($bodylen > 0) {
        	switch ($method) {
        	case 8:
            	// Currently the only supported compression method:
            	$data = gzinflate($body,$maxlength);
            	break;
        	default:
            	$error = "Unknown compression method.";
            	return false;
        	}
    	}  // zero-byte body content is allowed
    	// Verifiy CRC32
    	$crc   = sprintf("%u",crc32($data));
    	$crcOK = $crc == $datacrc;
    	$lenOK = $isize == strlen($data);
    	if (!$lenOK || !$crcOK) {
        	$error = ( $lenOK ? '' : 'Length check FAILED. ') . ( $crcOK ? '' : 'Checksum FAILED.');
        	return false;
    	}
    	return $data;
	}
}

/**
 * This file is part of the array_column library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) Ben Ramsey (http://benramsey.com)
 * @license http://opensource.org/licenses/MIT MIT
 */

if (!function_exists('array_column')) {
    /**
     * Returns the values from a single column of the input array, identified by
     * the $columnKey.
     *
     * Optionally, you may provide an $indexKey to index the values in the returned
     * array by the values from the $indexKey column in the input array.
     *
     * @param array $input A multi-dimensional array (record set) from which to pull
     *                     a column of values.
     * @param mixed $columnKey The column of values to return. This value may be the
     *                         integer key of the column you wish to retrieve, or it
     *                         may be the string key name for an associative array.
     * @param mixed $indexKey (Optional.) The column to use as the index/keys for
     *                        the returned array. This value may be the integer key
     *                        of the column, or it may be the string key name.
     * @return array
     */
    function array_column($input = null, $columnKey = null, $indexKey = null)
    {
        // Using func_get_args() in order to check for proper number of
        // parameters and trigger errors exactly as the built-in array_column()
        // does in PHP 5.5.
        $argc = func_num_args();
        $params = func_get_args();

        if ($argc < 2) {
            trigger_error("array_column() expects at least 2 parameters, {$argc} given", E_USER_WARNING);
            return null;
        }

        if (!is_array($params[0])) {
            trigger_error(
                'array_column() expects parameter 1 to be array, ' . gettype($params[0]) . ' given',
                E_USER_WARNING
            );
            return null;
        }

        if (!is_int($params[1])
            && !is_float($params[1])
            && !is_string($params[1])
            && $params[1] !== null
            && !(is_object($params[1]) && method_exists($params[1], '__toString'))
        ) {
            trigger_error('array_column(): The column key should be either a string or an integer', E_USER_WARNING);
            return false;
        }

        if (isset($params[2])
            && !is_int($params[2])
            && !is_float($params[2])
            && !is_string($params[2])
            && !(is_object($params[2]) && method_exists($params[2], '__toString'))
        ) {
            trigger_error('array_column(): The index key should be either a string or an integer', E_USER_WARNING);
            return false;
        }

        $paramsInput = $params[0];
        $paramsColumnKey = ($params[1] !== null) ? (string) $params[1] : null;

        $paramsIndexKey = null;
        if (isset($params[2])) {
            if (is_float($params[2]) || is_int($params[2])) {
                $paramsIndexKey = (int) $params[2];
            } else {
                $paramsIndexKey = (string) $params[2];
            }
        }

        $resultArray = array();

        foreach ($paramsInput as $row) {
            $key = $value = null;
            $keySet = $valueSet = false;

            if ($paramsIndexKey !== null && array_key_exists($paramsIndexKey, $row)) {
                $keySet = true;
                $key = (string) $row[$paramsIndexKey];
            }

            if ($paramsColumnKey === null) {
                $valueSet = true;
                $value = $row;
            } elseif (is_array($row) && array_key_exists($paramsColumnKey, $row)) {
                $valueSet = true;
                $value = $row[$paramsColumnKey];
            }

            if ($valueSet) {
                if ($keySet) {
                    $resultArray[$key] = $value;
                } else {
                    $resultArray[] = $value;
                }
            }

        }

        return $resultArray;
    }

}

