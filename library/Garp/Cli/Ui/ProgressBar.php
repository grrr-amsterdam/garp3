<?php
/**
 * Provides methods to display a progress bar in a Cli environment.
 *
 * @package Garp_Cli
 * @author  David Spreekmeester <david@grrr.nl>
 *
 * Usage:
 *  $progress = Garp_Cli_Ui_ProgressBar::getInstance();
 *  $progress->init(10);
 *  $progress->display("initializing...");
 *  $progress->advance();
 *  $progress->display("first of ten");
 *  $progress->advance(5);
 *  $progress->display("halfway");
 */
class Garp_Cli_Ui_ProgressBar extends Garp_Cli_Ui {
    protected $_isInteractive = true;

    protected $_totalProgressBlocks;

    /**
     * @var int Number of available columns for this terminal screen.
     */
    protected $_columns;

    const COMPLETE_BLOCK_CHAR = '█';
    const INCOMPLETE_BLOCK_CHAR = '▒';
    const SPACING = '  ';
    const DEFAULT_SCREEN_SIZE = 80;

    /**
     * @param int $totalValue   The total value of this progress bar, to be compared
     *                          with the current value provided in $this->render();
     * @return void
     */
    public function init($totalValue) {
        parent::init($totalValue);

        $this->_columns = $this->_detectNumberOfTerminalColumns();
        $this->_totalProgressBlocks = $this->_getBarWidthByScreenSize();
    }



    /**
     * Advances the progress bar by 1 step, if no argument is provided.
     * Otherwise, the progress bar is set to the provided value.
     *
     * @param Int $newValue The new value. Leave empty to advance 1 step.
     *                      This will be compared to $this->_totalValue.
     * @return void
     */
    public function advance($newValue = null) {
        parent::advance($newValue);

        $this->_preventOverflow();
    }


    /**
     * Output the progressbar to the screen.
     *
     * @param string $message           Optional message displayed next to the progress bar.
     * @param string $itemsLeftMessage  Indicate optional remaining value position with '%s'.
     *                                  If you want to use this param, provide $message as well.
     * @return void
     */
    public function display($message = null, $itemsLeftMessage = null) {
        $this->_verifyTotalValue();
        $this->_clearLine();
        $this->_renderProgress();

        if ($message) {
            echo self::SPACING;
            $itemsLeft = $this->_totalValue - $this->_currentValue;
            $itemsLeft = number_format($itemsLeft, 0, ',', '.');

            $output = $message . ', ' . sprintf($itemsLeftMessage, $itemsLeft);
            echo substr($output, 0, $this->_getMaximumMessageLength());
        }
    }

    public function displayError($string) {
        return Garp_Cli::errorOut($string);
    }

    public function displayHeader($string) {
        return Garp_Cli::lineOut("\n" . $string);
    }

    /**
     * Returns the current progress value. For custom purposes.
     *
     * @return int
     */
    public function getProgress() {
        return $this->_currentValue;
    }

    protected function _preventOverflow() {
        if ($this->_currentValue > $this->_totalValue) {
            $this->_currentValue = $this->_totalValue;
        }
    }

    protected function _verifyTotalValue() {
        if (is_null($this->_totalValue)) {
            throw new Exception(
                "The total value was not set for this progress bar. " .
                "Please use the init(\$totalValue) method when initiating a progress bar."
            );
        }
    }

    protected function _renderProgress() {
        $percentage = $this->_totalValue ?
            $this->_currentValue / $this->_totalValue :
            0;
        $completeBlocks = $this->_totalValue ?
            ceil($percentage * $this->_totalProgressBlocks) :
            $this->_totalProgressBlocks
        ;
        $incompleteBlocks = $this->_totalValue ?
            $this->_totalProgressBlocks - $completeBlocks :
            0
        ;

        for ($b = 0; $b < $completeBlocks; $b++) {
            echo "\033[2;32m" . self::COMPLETE_BLOCK_CHAR . "\033[0m";
        }

        for ($b = 0; $b < $incompleteBlocks; $b++) {
            echo self::INCOMPLETE_BLOCK_CHAR;
        }
    }

    /**
     * Returns the limit of characters for a string next to a progressbar,
     * depending on the available screen size.
     *
     * @return int
     */
    protected function _getMaximumMessageLength() {
        return $this->_columns - ($this->_totalProgressBlocks + strlen(self::SPACING));
    }

    protected function _clearLine() {
        echo "\033[2K";
        echo str_repeat(chr(8), 1000);
    }

    /**
     * Returns the number of columns in the current terminal screen.
     *
     * @return int Number of available colums
     */
    protected function _detectNumberOfTerminalColumns() {
        $terminalCols = null;
        try {
            exec('tput cols 2> /dev/null', $terminalCols, $errorCode);

            if ($errorCode == 0 && $terminalCols && array_key_exists(0, $terminalCols)) {
                return (int)$terminalCols[0];
            }
        } catch (Exception $e) {
        }

        return self::DEFAULT_SCREEN_SIZE;
    }

    protected function _getBarWidthByScreenSize() {
        if ($this->_columns < 40) {
            return 5;
        } elseif ($this->_columns < 70) {
            return 10;
        } elseif ($this->_columns < 80) {
            return 15;
        }
        return 20;
    }
}
