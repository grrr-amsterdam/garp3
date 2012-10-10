<?php
/**
 * Garp Cli Interface
 * This file follows /public/index.php more or less, to ensure
 * a same kind of environment as with a usual page request.
 */
date_default_timezone_set('Europe/Amsterdam');

// Check if APPLICATION_ENV is passed along as an argument.
foreach ($_SERVER['argv'] as $key => $arg) {
	if (substr($arg, 0, 17) === '--APPLICATION_ENV' ||
		substr($arg, 0, 3)  === '--e') {
		$keyAndVal = explode('=', $arg);
		define('APPLICATION_ENV', trim($keyAndVal[1]));
		// Remove APPLICATION_ENV from the arguments list
		array_splice($_SERVER['argv'], $key, 1);
	}
}
// Define application environment if it was not passed along as an argument
if (!defined('APPLICATION_ENV')) {
	if (getenv('APPLICATION_ENV')) {
		define('APPLICATION_ENV', getenv('APPLICATION_ENV'));
	} else {
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

/**
 * Report errors, since we're in CLI.
 * Note that log_errors = 1, which outputs to STDERR. display_errors however outputs to STDOUT. In a CLI
 * environment this results in a double error. display_errors is therefore set to 0 so that STDERR is 
 * the only stream showing errors.
 * @see http://stackoverflow.com/questions/9001911/why-are-php-errors-printed-twice
 */
error_reporting(-1);
ini_set('log_errors', 0);
ini_set('display_startup_errors', 1);
ini_set('display_errors', 'stderr');

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
if (isset($classLoader)) {
	if ($classLoader->isLoadable('App_Cli_Command_'.$args[0])) {
		$commandName = 'App_Cli_Command_'.$args[0];
	}
}
unset($args[0]);

if (isset($classLoader) && !$classLoader->isLoadable($commandName)) {
	Garp_Cli::errorOut('Silly developer. This is not the command you\'re looking for.');
	exit;
}
$command = new $commandName();
if (!$command instanceof Garp_Cli_Command) {
	Garp_Cli::errorOut('Error: '.$commandName.' is not a valid Command. Command must implement Garp_Cli_Command.');
	exit;
}
$command->main($args);
exit;
