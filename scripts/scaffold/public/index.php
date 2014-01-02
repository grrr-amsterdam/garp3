<?php
date_default_timezone_set('Europe/Amsterdam');
define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));
require_once("../garp/application/init.php");


// Create application, bootstrap, and run
$application = new Garp_Application(
	APPLICATION_ENV, 
	APPLICATION_PATH.'/configs/application.ini'
);
$application->bootstrap()->run();