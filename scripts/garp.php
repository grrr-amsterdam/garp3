<?php
/**
 * Garp Cli Interface
 * This file follows /public/index.php more or less, to ensure
 * a same kind of environment as with a usual page request.
 */
date_default_timezone_set('Europe/Amsterdam');

// Define application environment
if (getenv('APPLICATION_ENV')) {
	define('APPLICATION_ENV', getenv('APPLICATION_ENV'));
} else {
	// Check if APPLICATION_ENV is passed along as an argument.
	foreach ($_SERVER['argv'] as $key => $arg) {
		if (substr($arg, 0, 17) === '--APPLICATION_ENV') {
			$keyAndVal = explode('=', $arg);
			define('APPLICATION_ENV', trim($keyAndVal[1]));
			array_splice($_SERVER['argv'], $key, 1);
		}
	}

	if (!defined('APPLICATION_ENV')) {
		require_once(dirname(__FILE__)."/../../library/Garp/Cli.php");
		Garp_Cli::errorOut("APPLICATION_ENV is not set. Please set it as a shell variable or pass it along as an argument, like so: --APPLICATION_ENV=development");
		exit;
	}
}

require_once(dirname(__FILE__)."/../application/init.php");


// Create application, bootstrap, and run
$application = new Garp_Application(
	APPLICATION_ENV, 
	APPLICATION_PATH.'/configs/application.ini'
);
$application->bootstrap();
// save the application in the registry, so it can be used by commands.
Zend_Registry::set('application', $application);

//	report errors, since we're in CLI
error_reporting(-1);
ini_set('display_errors', 'stdout');
ini_set('display_startup_errors', true);

/**
 * Process the command
 */
$args = Garp_Cli::parseArgs($_SERVER['argv']);
if (empty($args[0])) {
	Garp_Cli::errorOut('No command given.');
	Garp_Cli::errorOut('Usage: php garp.php <command> [args,..]');
	exit;
}

/* Construct command classname */
$commandName = 'Garp_Cli_Command_'.$args[0];
unset($args[0]);
$command = new $commandName();
if (!$command instanceof Garp_Cli_Command) {
	Garp_Cli::errorOut('Error: '.$commandName.' is not a valid Command. Command must implement Garp_Cli_Command.');
	exit;
}
$command->main($args);
exit;
