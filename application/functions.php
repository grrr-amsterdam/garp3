<?php
/**
 * Instantiate a model.
 *
 * @param  string $modelSuffix
 * @return Garp_Model_Db
 */
function model(string $modelSuffix) {
    return instance("Model_{$modelSuffix}");
}

/**
 * Shortcut to logging messages.
 *
 * @param string $file Basename of a log file. Extension may be omitted.
 *                     File will end up in /application/data/logs
 * @param string $message Your log message. Arrays will be print_r'd.
 * @param int $priority A Zend_Log priority (e.g. INFO, NOTICE, WARN etc.)
 * @return void
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
 *
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
 * Shortcut to a "no opereration" function.
 *
 * @return callable
 */
function noop() {
    return function () {
    };
}

/**
 * Make the PHP language a little more expressive.
 * PHP 5.4 allows chaining of new instances like so;
 * (new Instance())->doSomething();
 * This method sort of brings this to earlier versions of PHP:
 * instance(new Instance())->doSomething();
 *
 * @param object $obj
 * @return object
 */
function instance($obj) {
    if (is_string($obj)) {
        $obj = new $obj;
    }
    return $obj;
}

/**
 * Transform array of objects into a new array with just the given key of said objects
 *
 * @param array $array
 * @param string $column
 * @return array
 */
// Note: coding standards are ignored here because "array_" in "array_pluck" is perceived as package
// name but it's instead chosen to be in line with existing php functions.
// @codingStandardsIgnoreStart
function array_pluck($array, $column) {
    return array_map(function($obj) use ($column) {
        return isset($obj[$column]) ? $obj[$column] : null;
    }, $array);
}
// @codingStandardsIgnoreEnd

/**
 * Returns TRUE if $callback returns true for one of the items in the collection.
 *
 * @param array $collection
 * @param callable $callback
 * @return bool
 */
function some($collection, $callback) {
    foreach ($collection as $index => $item) {
        if (call_user_func($callback, $item, $index)) {
            return true;
        }
    }
    return false;
}

/**
 * Changes the number of arguments accepted by the given function into 1.
 *
 * @param callable $fn
 * @return callable
 */
function unary($fn) {
    return function ($arg) use ($fn) {
        return call_user_func($fn, $arg);
    };
}

/**
 * Flatten an array of arrays.
 * The cornerstone of functional programming.
 *
 * @param array $array
 * @return array
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
 *
 * Overloaded to accept a single argument, in which case it'll return a curried function waiting for
 * the array.
 * In that case the default value will always be NULL.
 * Example:
 * $a = array('foo' => 123, 'bar' => 456);
 * array_get('foo')($a); // 123
 * array_get('baz')($a); // null
 *
 * @param array $a
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
// Note: coding standards are ignored here because "array_" in "array_get" is perceived as package
// name but it's instead chosen to be in line with existing php functions.
// @codingStandardsIgnoreStart
function array_get($a, $key = null, $default = null) {
    if (func_num_args() === 1) {
        $key = $a;
        return function ($a) use ($key) {
            return array_get($a, $key);
        };
    }
    return isset($a[$key]) ? $a[$key] : $default;
}
// @codingStandardsIgnoreEnd

/**
 * Create a new array containing only the keys from the original that you want.
 * Example:
 * $my_array = array(
 *   'name' => 'Henk',
 *   'occupation' => 'Doctor',
 *   'age' => 43,
 *   'country' => 'Zimbabwe'
 * );
 * array_get_subset($my_array, array('name', 'country'))
 *
 * Returns array('name' => 'Henk', 'country' => 'Zimbabwe')
 *
 * @param array $a
 * @param array $allowed
 * @return array
 */
// Note: coding standards are ignored here because "array_" in "array_get_subset" is perceived as
// package name but it's instead chosen to be in line with existing php functions.
// @codingStandardsIgnoreStart
function array_get_subset(array $a, array $allowed) {
    return array_intersect_key($a, array_flip($allowed));
}
// @codingStandardsIgnoreEnd

/**
 * Array setter in function form
 *
 * @param string $key
 * @param mixed $value
 * @param array $a
 * @return array
 */
// Note: coding standards are ignored here because "array_" in "array_get" is perceived as package
// name but it's instead chosen to be in line with existing php functions.
// @codingStandardsIgnoreStart
function array_set($key, $value, $a = null) {
    if (func_num_args() === 2) {
        return callLeft('array_set', $key, $value);
    }
    $a[$key] = $value;
    return $a;
}
// @codingStandardsIgnoreEnd

/**
 * Pure sort function. Returns a sorted copy.
 *
 * @param callable $fn
 * @param array $a
 * @return array
 */
function psort($fn = null, array $a = null) {
    if ($fn && !is_callable($fn)) {
        throw new InvalidArgumentException('psort expects parameter 1 to be a valid callback');
    }
    $sorter = function ($a) use ($fn) {
        // make a copy of the array as to not disturb the original
        $b = $a;
        if (!$fn) {
            sort($b);
            return $b;
        }
        usort($b, $fn);
        return $b;
    };
    if (is_null($a)) {
        return $sorter;
    }
    return $sorter($a);
}

/**
 * Returns a property from an object or NULL if unavailable.
 * Is curried to ease use with array_map and array_filter and the like.
 *
 * Usage:
 * // returns $object->name or NULL
 * $name = getProperty('name', $object);
 *
 * // returns list of objects that have a truthy name property
 * $objectsWithName = array_filter($objects, getProperty('name'));
 *
 * @param string $key The property
 * @param object $obj
 * @return mixed
 */
function getProperty($key, $obj = null) {
    $getter = function ($obj) use ($key) {
        return property_exists($obj, $key) ? $obj->{$key} : null;
    };

    if (is_null($obj)) {
        return $getter;
    }
    return $getter($obj);
}

/**
 * Returns true if a property on an object equals the given value.
 * Is curried to ease use with array_map and array_filter and the like.
 *
 * Usage:
 * // returns TRUE if $object contains a property 'name' with value 'John'
 * $isNamedJohn = propertyEquals('name', 'John', $object);
 *
 * // returns list of object that have a property 'name' with value 'John'
 * $objectsNamesJohn = array_filter($objects, propertyEquals('name', 'John'));
 *
 * @param string $key The property
 * @param mixed $value
 * @param object|array $obj
 * @return bool
 */
function propertyEquals($key, $value, $obj = null) {
    $checker = function ($obj) use ($key, $value) {
        if (is_array($obj)) {
            return array_get($obj, $key) === $value;
        }
        return getProperty($key, $obj) === $value;
    };
    if (is_null($obj)) {
        return $checker;
    }
    return $checker($obj);
}

/**
 * Curried method caller.
 * Can be used by array_filter and the like.
 *
 * Usage:
 * Image an array of User objects, all supporting a `getName()` method.
 * Mapping the array of User objects to an array of names could be done as follows:
 *
 * $names = array_map(callMethod('getName', array()), $objects);
 *
 * @param string $method
 * @param array  $args
 * @param object $obj
 * @return mixed
 */
function callMethod($method, array $args, $obj = null) {
    $caller = function ($obj) use ($method, $args) {
        return call_user_func_array(array($obj, $method), $args);
    };
    if (is_null($obj)) {
        return $caller;
    }
    return $caller($obj);
}

/**
 * Partially apply a function where initial arguments form the 'left' arguments of the function, and
 * the new function will accept the rest arguments on the right side of the signature.
 *
 * Example:
 * function sayHello($to, $from, $message) {
 *   return "Hello {$to}, {$from} says '{$message}'";
 * }
 *
 * $sayHelloToJohn = callLeft('sayHello', 'John');
 * $sayHelloToJohn('Hank', "How's it going?"); // Hello John, Hank says 'How's it going?'
 *
 * Note: the function signature doesn't show the rest parameters. This is confusing, but
 * unfortunately we have to support PHP5.3. In PHP5.6 the signature would have been
 *
 * ```
 * function callLeft($fn, ...$args)
 * ```
 *
 * @param callable $fn The partially applied function
 * @return callable
 */
function callLeft($fn) {
    $args = array_slice(func_get_args(), 1);
    return function () use ($fn, $args) {
        $remainingArgs = func_get_args();
        return call_user_func_array($fn, array_merge($args, $remainingArgs));
    };
}

/**
 * Partially apply a function where initial arguments form the 'right' arguments of the function,
 * and the new function will accept the rest arguments on the left side of the signature.
 *
 * Example:
 * function sayHello($to, $from, $message) {
 *   return "Hello {$to}, {$from} says '{$message}'";
 * }
 *
 * $askDirections = callRight('sayHello', "Where's the supermarket?");
 * $askDirections('John', 'Hank'); // Hello John, Hank says 'Where's the supermarket?'
 *
 * We can it further by saying:
 * $lindaAsksDirections = callRight('sayHello', 'Linda', "Where's the supermarket?");
 * $lindaAsksDirections('John'); // Hello John, Linda says 'Where's the supermarket?'
 *
 * Note: the function signature doesn't show the rest parameters. This is confusing, but
 * unfortunately we have to support PHP5.3. In PHP5.6 the signature would have been
 *
 * ```
 * function callRight($fn, ...$args)
 * ```
 *
 * @param callable $fn The partially applied function
 * @return callable
 */
function callRight($fn) {
    $args = array_slice(func_get_args(), 1);
    return function () use ($fn, $args) {
        $remainingArgs = func_get_args();
        return call_user_func_array($fn, array_merge($remainingArgs, $args));
    };
}

/**
 * Creates a negative version of an existing function.
 *
 * Example:
 * $a = ['a', 'b', 'c'];
 * in_array('a', $a); // true
 *
 * not('in_array')('a'); // false
 * not('in_array')('d'); // true
 *
 * @param callable $fn Anything that call_user_func_array accepts
 * @return callable
 */
function not($fn) {
    return function () use ($fn) {
        $args = func_get_args();
        return !call_user_func_array($fn, $args);
    };
}

/**
 * Returns the given argument
 *
 * @param mixed $it
 * @return mixed
 */
function id($it = null) {
    if (!func_num_args()) {
        return callLeft('id');
    }
    return $it;
}

/**
 * A functional programming classic.
 * Compose functions $g and $f into a new function $gf
 *
 * Note that evaluation is from right to left.
 * Usage:
 * $reverseAndToUpper = compose('ucfirst', 'strrev');
 *
 * @param callable $f
 * @param callable $g
 * @return callable
 */
function compose($f, $g) {
    return function () use ($f, $g) {
        $args = func_get_args();
        return call_user_func_array(
            $f,
            array(call_user_func_array($g, $args))
        );
    };
}

/**
 * Ternary operator in function-form.
 * Allows functions for the first 3 arguments in which case $subject will be passed into them to get
 * to the result, as well as determine which branch to take.
 *
 * Usage:
 * $isAllowedEditing = when(propertyEquals('role', 'admin'), true, false, $user);
 *
 * array_map(
 *   when(
 *     propertyEquals('role', 'admin'),
 *     array_set('can_edit', true),
 *     array_set('can_edit', false)
 *   ),
 *   $users
 * )
 *
 * @param mixed $condition
 * @param mixed $ifTrue
 * @param mixed $ifFalse
 * @param mixed $subject
 * @return mixed
 */
function when($condition, $ifTrue, $ifFalse, $subject = null) {
    if (func_num_args() === 3
        && (is_callable($condition) || is_callable($ifTrue) || is_callable($ifFalse))
    ) {
        return callLeft('when', $condition, $ifTrue, $ifFalse);
    }
    $passed = is_callable($condition) ? call_user_func($condition, $subject) : $condition;
    if ($passed) {
        return is_callable($ifTrue) ? call_user_func($ifTrue, $subject) : $ifTrue;
    }
    return is_callable($ifFalse) ? call_user_func($ifFalse, $subject) : $ifFalse;
}

// Ignoring coding standards because the following is all third party code
// @codingStandardsIgnoreStart
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
// @codingStandardsIgnoreEnd
