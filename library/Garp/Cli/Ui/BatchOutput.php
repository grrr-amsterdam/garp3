<?php
/**
 * Provides methods to display progress in a batch (non-interactive) context.
 *
 * @package Garp_Cli
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Cli_Ui_BatchOutput extends Garp_Cli_Ui {
    protected $_isInteractive = false;

    public function display($message = null, $itemsLeftMessage = null) {
        echo $message . '. ';
    }

    public function displayHeader($string) {
        echo $string . ': ';
    }

    public function displayError($string) {
        return Garp_Cli::errorOut($string);
    }
}
