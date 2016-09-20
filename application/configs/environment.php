<?php
function determineEnvironment($host) {
    $regexps = array(
    '/^staging\./' => 'staging',
    '/integration.grrr.nl$/' => 'integration',
    '/^localhost\./' => 'development'
    );
    foreach ($regexps as $re => $env) {
        if (preg_match($re, $host)) {
            return $env;
        }
    }
    return 'production';
}

$memcachedPorts = array(
    'production'  => 11211,
    'staging'     => 11211,
    'development' => null,
    'testing'     => null
);

defined('APPLICATION_ENV') || define(
    'APPLICATION_ENV', (getenv('APPLICATION_ENV') ?:
    determineEnvironment(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ''))
);

defined('MEMCACHE_PORT') || define(
    'MEMCACHE_PORT',
    array_key_exists(APPLICATION_ENV, $memcachedPorts) ? $memcachedPorts[APPLICATION_ENV] :
       $memcachedPorts['development']
);

