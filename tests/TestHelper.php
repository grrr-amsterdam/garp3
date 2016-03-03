<?php
date_default_timezone_set('Europe/Amsterdam');
define('APPLICATION_ENV', 'testing');
$rootPath = dirname(__FILE__).'/..';

require_once $rootPath.'/application/init.php';

$application = new Garp_Application(
	APPLICATION_ENV,
	GARP_APPLICATION_PATH . '/../tests/config.ini'
	/*
	array(
		'bootstrap' => array(
			'path' => GARP_APPLICATION_PATH . "/../library/Garp/Application/Bootstrap/Bootstrap.php",
			'class' => 'Garp_Application_Bootstrap_Bootstrap'
		),
		'resources' => array(
			'view' => array(
				'doctype' => 'html5'
			),
			'db' => array(
			),
			'locale' => array(
			)
		),
	)
	 */


	//APPLICATION_PATH.'/configs/application.ini'
);

$application->bootstrap();
Zend_Registry::set('application', $application);

$mem = new Garp_Util_Memory();
$mem->useHighMemory();

error_reporting(-1);
ini_set('log_errors', 0);
ini_set('display_startup_errors', 1);
ini_set('display_errors', 'stderr');
