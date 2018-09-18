<?php
/**
 * Garp_Cli_Command
 * Blueprint for command line commands (usually triggered
 * from /garp/scripts/garp.php).
 *
 * @package Garp_Cli
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
abstract class Garp_Cli_Command {
    /**
     * Restrict arguments
     *
     * @var array
     */
    protected $_allowedArguments = array();

    /**
     * Data given thru STDIN
     *
     * @var string
     */
    protected $_stdin = '';

    /**
     * Class constructor
     *
     * @param string $stdin Data piped into the script using STDIN
     * @return void
     */
    public function __construct($stdin = '') {
        Zend_Session::$_unitTestEnabled = true;
        $this->_stdin = $stdin;
    }

    /**
     * Central start method
     * By default expects the first parameter (index 1 in $args) to be the requested method.
     *
     * @param array $args Various options.
     *                    Must contain at least a method name as the first parameter.
     * @return bool
     */
    public function main(array $args = array()) {
        $publicMethods = $this->getPublicMethods();
        if (!array_key_exists(0, $args)) {
            if (in_array('help', $publicMethods)) {
                $args[0] = 'help';
            } else {
                Garp_Cli::errorOut(
                    "No method selected. Available methods: \n " .
                    implode("\n ", $publicMethods)
                );
                return false;
            }
        }

        $methodName = $args[0];
        if (!in_array($methodName, $publicMethods)) {
            Garp_Cli::errorOut('Unknown command \'' . $methodName . '\'');
            return false;
        }
        unset($args[0]);
        $args = $this->_remapArguments($args);
        if (!$this->_validateArguments($methodName, $args)) {
            // Note, _validateArguments also provides the CLI feedback
            return false;
        }
        $result = call_user_func_array(array($this, $methodName), array($args));
        return $result;
    }

    /**
     * Assists in bash completion
     *
     * @param array $args
     * @return bool
     */
    public function complete(array $args = array()) {
        $publicMethods = $this->getPublicMethods();
        $ignoredMethods = array('complete');
        $publicMethods = array_diff($publicMethods, $ignoredMethods);
        Garp_Cli::lineOut(implode(' ', $publicMethods));
        return true;
    }

    /**
     * Return a list of all public methods available on this command.
     *
     * @return array
     */
    public function getPublicMethods() {
        $reflect = new ReflectionClass($this);
        $publicMethods = $reflect->getMethods(ReflectionMethod::IS_PUBLIC);
        $publicMethods = array_map(
            function ($m) {
                return $m->name;
            },
            $publicMethods
        );
        $publicMethods = array_filter(
            $publicMethods,
            function ($m) {
                $ignoreMethods = array('__construct', 'main', 'getPublicMethods');
                return !in_array($m, $ignoreMethods);
            }
        );
        return $publicMethods;
    }

    /**
     * Remap the numeric keys of a given arguments array, so they make sense in a different
     * context.
     * For example, this command:
     * $ garp/scripts/garp Db replace monkeys hippos
     * would result in the following arguments array:
     * [0] => Db
     * [1] => replace
     * [2] => monkeys
     * [3] => hippos
     *
     * When this abstract class passes along the call to a specific command, in this case
     * Garp_Cli_Command_Db::replace(), it's better to start the array at index 0 being "monkeys".
     *
     * @param array $args
     * @return array
     *
     * @todo I'm guessing array_splice() would be a better choice here...
     */
    protected function _remapArguments(array $args = array()) {
        $out = array();
        $i = 0;
        foreach ($args as $key => $value) {
            if (is_numeric($key)) {
                $out[$i++] = $value;
            } else {
                $out[$key] = $value;
            }
        }
        return $out;
    }

    /**
     * Make sure the method is not inadvertently called with the
     * wrong arguments. This might indicate the user made a mistake
     * in calling it.
     *
     * @param string $methodName
     * @param array $args
     * @return bool
     */
    protected function _validateArguments($methodName, $args) {
        if (!array_key_exists($methodName, $this->_allowedArguments)
            || $this->_allowedArguments[$methodName] === '*'
        ) {
            return true;
        }

        $unknownArgs = array_diff(array_keys($args), $this->_allowedArguments[$methodName]);
        if (!count($unknownArgs)) {
            return true;
        }
        // Report the first erroneous argument
        $errorStr = current($unknownArgs);
        // Show the value of the argument if the index is numeric
        if (is_numeric($errorStr)) {
            $errorStr = $args[$unknownArgs[0]];
        }
        Garp_Cli::errorOut('Unexpected argument ' . $errorStr);
        return false;
    }

}

