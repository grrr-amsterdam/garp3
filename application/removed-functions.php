<?php

/**
 * Instantiate a model.
 *
 * @param  string $modelSuffix
 * @return Garp_Model_Db
 *
 * @deprecated Use `new Model_$modelSuffix()`
 */
function model(string $modelSuffix) {
    $class = "Model_{$modelSuffix}";
    return new $class();
}

/**
 * Quick access to the view.
 *
 * @return Zend_View_Abstract
 *
 * @deprecated Use `Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view`
 */
function view(): Zend_View_Abstract {
    return Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view;
}


/**
 * Shortcut to logging messages.
 *
 * @param string $file Basename of a log file. Extension may be omitted.
 *                     File will end up in /application/data/logs
 * @param string $message Your log message. Arrays will be print_r'd.
 * @param int $priority A Zend_Log priority (e.g. INFO, NOTICE, WARN etc.)
 * @return void
 *
 * @deprecated Use `Garp_Log::factory($file)->log($message, Zend_Log::INFO)`
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
 * Shortcut to a "no opereration" function.
 *
 * @return callable
 *
 * @deprecated Use `function() {}`
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
 *
 * @deprecated Use `new $obj;`
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
 *
 * @deprecated Use `f\map(f\prop($column), $array)`
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
 *
 * @deprecated Use `f\some($callback, $collection)`  @see https://grrr-amsterdam.github.io/garp-functional/#some
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
 *
 * @deprecated Use `f\unary($fn)`  @see https://grrr-amsterdam.github.io/garp-functional/#unary
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
 *
 * @deprecated Use `f\flatten` @see https://grrr-amsterdam.github.io/garp-functional/#flatten
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
 *
 * @deprecated Use `f\either(f\prop($key, $a), $default)` @see https://grrr-amsterdam.github.io/garp-functional/#either
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
 *
 * @deprecated Use `f\pick($allowed, $a)` @see https://grrr-amsterdam.github.io/garp-functional/#pick
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
 *
 * @deprecated Use `f\prop_set` @see https://grrr-amsterdam.github.io/garp-functional/#prop_set
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
 *
 * @deprecated Use `f\sort_by($fn)` @see https://grrr-amsterdam.github.io/garp-functional/#sort_by
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
 *
 * @deprecated Use `f\prop($key, $obj)` @see https://grrr-amsterdam.github.io/garp-functional/#prop
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
 *
 * @deprecated Use `f\prop_equals($key, $value, $obj)` @see https://grrr-amsterdam.github.io/garp-functional/#prop_equals
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
 *
 * @deprecated Use `f\call($method, $args, $obj)` @see https://grrr-amsterdam.github.io/garp-functional/#call
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
 *
 * @deprecated Use `f\partial($fn, ...$args)` @see https://grrr-amsterdam.github.io/garp-functional/#partial
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
 *
 * @deprecated Use `f\partial_right($fn, ...$args)` @see https://grrr-amsterdam.github.io/garp-functional/#partial_right
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
 *
 * @deprecated Use `f\not($fn)` @see https://grrr-amsterdam.github.io/garp-functional/#not
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
 *
 * @deprecated Use `f\id($it)` @see https://grrr-amsterdam.github.io/garp-functional/#id
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
 *
 * @deprecated Use `f\compose($f, $g)` @see https://grrr-amsterdam.github.io/garp-functional/#compose
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
 *
 * @deprecated Use `f\when($condition, $ifTrue, $ifFalse, $subject)` @see https://grrr-amsterdam.github.io/garp-functional/#when
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
