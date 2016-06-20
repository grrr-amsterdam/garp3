<?php
/**
 * Garp_Util_TimedTask
 * Execute a task, record its execution time, done.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      Garp_Util
 */
class Garp_Util_TimedTask {
    const FORMAT_SECONDS = 0;
    const FORMAT_MINUTES = 1;
    const FORMAT_HOURS = 2;

    protected $_units = array(
        self::FORMAT_SECONDS => 1,
        self::FORMAT_MINUTES => 60,
        self::FORMAT_HOURS   => 3600,
    );

    /** @var Anything that gets accepted by call_user_func */
    protected $_executable;

    /** @var Array */
    protected $_args;

    /** @var Bool */
    protected $_hasRun = false;

    /** @var Int */
    protected $_timeTaken = 0;

    public function __construct($executable, array $args = array()) {
        $this->_executable = $executable;
        $this->_args = $args;
    }

    public function perform() {
        $timeStart = microtime(true);
        $response = call_user_func_array($this->_executable, $this->_args);
        $timeEnd = microtime(true);
        $time = $timeEnd - $timeStart;
        $this->_timeTaken = $time;
        $this->_hasRun = true;
        return $response;
    }

    public function getTime($format = false) {
        if (!$this->hasRun()) {
            throw new LogicException('Task has yet to run.');
        }

        if ($format === false) {
            return $this->_timeTaken;
        }
        if (!array_key_exists($format, $this->_units)) {
            throw new InvalidArgumentException('Unknown format.');
        }
        return number_format($this->_timeTaken / $this->_units[$format], 2);
    }

    public function hasRun() {
        return $this->_hasRun;
    }
}
