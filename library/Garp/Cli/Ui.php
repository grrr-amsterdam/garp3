<?php
/**
 * Generic extendable singleton class to deal with Cli output and input.
 *
 * @package Garp_Cli
 * @author  David Spreekmeester <david@grrr.nl>
 */
abstract class Garp_Cli_Ui implements Garp_Cli_Ui_Protocol {
    protected $_totalValue = null;
    protected $_currentValue = 0;
    protected $_isInteractive = false;

    public static function getInstance() {
        static $ui = null;
        if ($ui === null) {
            $uiClass = get_called_class();
            $ui = new $uiClass();
        }
        return $ui;
    }

    protected function __construct() {
    }

    /**
     * @param int $totalValue The total value of this process.
     * @return void
     */
    public function init($totalValue) {
        $this->_totalValue = $totalValue;
        $this->_currentValue = 0;
    }

    /**
     * Advances the progress bar by 1 step, if no argument is provided.
     * Otherwise, the progress bar is set to the provided value.
     *
     * @param int $newValue The new value. Leave empty to advance 1 step.
     *                      This will be compared to $this->_totalValue.
     * @return void
     */
    public function advance($newValue = null) {
        if (!is_null($newValue)) {
            $this->_currentValue = $newValue;
            return;
        }

        $this->_currentValue++;
    }

    public function isInteractive() {
        return $this->_isInteractive;
    }
}
