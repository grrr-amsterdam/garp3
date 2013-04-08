<?php
date_default_timezone_set('Europe/Amsterdam');
define('APPLICATION_ENV', 'testing');
$rootPath = dirname(__FILE__).'/..';

require_once $rootPath.'/application/init.php';

$application = new Garp_Application(
	APPLICATION_ENV,
	APPLICATION_PATH.'/configs/application.ini'
);

$application->bootstrap();
Zend_Registry::set('application', $application);

$mem = new Garp_Util_Memory();
$mem->useHighMemory();

error_reporting(-1);
ini_set('display_errors', 'stdout');
ini_set('display_startup_errors', true);
