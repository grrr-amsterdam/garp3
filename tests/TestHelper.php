<?php
date_default_timezone_set('Europe/Amsterdam');
define('APPLICATION_ENV', 'testing');
$garpRoot = dirname(__FILE__).'/..';

error_reporting(-1);
ini_set('log_errors', 0);
ini_set('display_startup_errors', 1);
ini_set('display_errors', 'stderr');

require_once $garpRoot . '/application/init.php';

// Grab either the configuration of a host project, where garp3 is installed as dependency,
// or take predefined config.ini used in Garp's own test suite.
$assumedApplicationIniPath = APPLICATION_PATH . '/configs/application.ini';
$application = new Garp_Application(
    APPLICATION_ENV,
    file_exists($assumedApplicationIniPath) ?
        $assumedApplicationIniPath :
        GARP_APPLICATION_PATH . '/../tests/config.ini'
);

$application->bootstrap();
Zend_Registry::set('application', $application);

$mem = new Garp_Util_Memory();
$mem->useHighMemory();
