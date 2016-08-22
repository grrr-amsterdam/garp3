<?php
/**
 * Garp Cli Interface
 * This file follows /public/index.php more or less, to ensure
 * a same kind of environment as with a usual page request.
 *
 * @package Garp
 * @author Harmen Janssen <harmen@grrr.nl>
 */
date_default_timezone_set('Europe/Amsterdam');

// Check if APPLICATION_ENV is passed along as an argument.
foreach ($_SERVER['argv'] as $key => $arg) {
    if (substr($arg, 0, 17) === '--APPLICATION_ENV'
        || substr($arg, 0, 3)  === '--e'
    ) {
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
        include_once dirname(__FILE__) . '/../library/Garp/Cli.php';
        Garp_Cli::errorOut(
            "APPLICATION_ENV is not set. Please set it as a shell variable or ' .
            'pass it along as an argument, like so: --e=development"
        );
        // @codingStandardsIgnoreStart
        exit(1);
        // @codingStandardsIgnoreEnd
    }
}

$basePath = realpath(dirname(__FILE__) . '/..');
if (basename(realpath($basePath . '/../../')) === 'vendor') {
    // Set BASE_PATH to be the root of the host project
    $basePath = realpath(dirname(__FILE__) . '/../../../../');
}
define('BASE_PATH', $basePath);

if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    include_once BASE_PATH . '/vendor/autoload.php';
}
// Include new-style environment configuration. This sets memcache ports
if (file_exists(BASE_PATH . '/application/configs/environment.php')) {
    include_once BASE_PATH . '/application/configs/environment.php';
}

require_once dirname(__FILE__) . "/../application/init.php";

// Create application, bootstrap, and run
$applicationIni = APPLICATION_PATH . '/configs/application.ini';
try {
    $application = new Garp_Application(APPLICATION_ENV, $applicationIni);
    $application->bootstrap();
} catch (Garp_Config_Ini_InvalidSectionException $e) {
    Garp_Cli::errorOut('Invalid environment: ' . APPLICATION_ENV);
    Garp_Cli::lineOut(
        "Valid options are: \n- " .
        implode("\n- ", $e->getValidSections()), Garp_Cli::BLUE
    );
    // @codingStandardsIgnoreStart
    exit(1);
    // @codingStandardsIgnoreEnd
}
// save the application in the registry, so it can be used by commands.
Zend_Registry::set('application', $application);

/**
 * Report errors, since we're in CLI.
 * Note that log_errors = 1, which outputs to STDERR. display_errors however outputs to STDOUT.
 * In a CLI environment this results in a double error. display_errors is therefore set to 0
 * so that STDERR is the only stream showing errors.
 *
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
    Garp_Cli::errorOut('Usage: g <command> [args,..]');
    // @codingStandardsIgnoreStart
    exit(1);
    // @codingStandardsIgnoreEnd
}

/* Construct command classname */
$classArgument = ucfirst($args[0]);
$namespaces = array('App', 'Garp');
$config = Zend_Registry::get('config');
if (!empty($config->cli->namespaces)) {
    $namespaces = $config->cli->namespaces->toArray();
}

$commandNames = array_map(
    function ($ns) use ($classArgument) {
        return $ns . '_Cli_Command_' . $classArgument;
    },
    $namespaces
);
$commandNames = array_filter($commandNames, 'class_exists');

// Remove the command name from the argument list
$args = array_splice($args, 1);

if (!count($commandNames)) {
    Garp_Cli::errorOut('Silly developer. This is not the command you\'re looking for.');
    // @codingStandardsIgnoreStart
    exit(1);
    // @codingStandardsIgnoreEnd
}
$commandName = current($commandNames);
$command = new $commandName();
if (!$command instanceof Garp_Cli_Command) {
    Garp_Cli::errorOut(
        'Error: ' . $commandName . ' is not a valid Command. ' .
        'Command must implement Garp_Cli_Command.'
    );
    // @codingStandardsIgnoreStart
    exit(1);
    // @codingStandardsIgnoreEnd
}

// Since localisation is based on a URL, and URLs are not part of a commandline, no
// translatation is loaded. But we might need it to convert system messages.
$commandsWithoutTranslation = array(
    'Spawn', 'Config', 'Gumball'
);
if (!in_array($classArgument, $commandsWithoutTranslation)) {
    if (!Zend_Registry::isRegistered('Zend_Translate')
        && Zend_Registry::isRegistered('Zend_Locale')
    ) {
        Zend_Registry::set(
            'Zend_Translate',
            Garp_I18n::getTranslateByLocale(Zend_Registry::get('Zend_Locale'))
        );
    }
}

$command->main($args);

// @codingStandardsIgnoreStart
exit(0);
// @codingStandardsIgnoreEnd

