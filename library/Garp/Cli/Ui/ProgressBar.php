<?php
/**
 * Provides methods to display a progress bar in a Cli environment.
 * @author David Spreekmeester | Grrr.nl
 * 
 * Usage:
 * 	$progress = Garp_Cli_Ui_ProgressBar::getInstance();
 * 	$progress->init(10);
 * 	$progress->display("initializing...");
 * 	$progress->advance();
 * 	$progress->display("first of ten");
 * 	$progress->advance(5);
 * 	$progress->display("halfway");
 */
class Garp_Cli_Ui_ProgressBar extends Garp_Cli_Ui {
	protected $_totalValue = null;
	protected $_currentValue = 0;
	protected $_totalProgressBlocks;
	
	/**
	 * @var Int Number of available columns for this terminal screen.
	 */
	protected $_columns;

	const COMPLETE_BLOCK_CHAR = '█';
	const INCOMPLETE_BLOCK_CHAR = '▒';
	const SPACING = '  ';
	const DEFAULT_SCREEN_SIZE = 80;


	/**
	 * @param Int $totalValue	The total value of this progress bar, to be compared
	 * 							with the current value provided in $this->render();
	 */
	public function init($totalValue) {
		$this->_columns = $this->_detectNumberOfTerminalColumns();
		$this->_totalProgressBlocks = $this->_getBarWidthByScreenSize();

		$this->_totalValue = $totalValue;
		$this->_currentValue = 0;
	}

	
	
	/**
	 * Advances the progress bar by 1 step, if no argument is provided.
	 * Otherwise, the progress bar is set to the provided value.
	 * 
	 * @param Int $newValue The new value. Leave empty to advance 1 step. This will be compared to $this->_totalValue.
	 */
	public function advance($newValue = null) {
		if (!is_null($newValue)) {
			$this->_currentValue = $newValue;
		} else $this->_currentValue++;

		$this->_preventOverflow();
	}


	/**
	 * Output the progressbar to the screen.
	 * @param String $message 	Optional message displayed next to the progress bar.
	 * 							Indicate optional remaining value position with %d.
	 */
	public function display($message = null) {
		$this->_verifyTotalValue();
		$this->_clearLine();
		$this->_renderProgress();

		if ($message) {
			echo self::SPACING;
			$output = sprintf($message, $this->_totalValue - $this->_currentValue);
			echo substr($output, 0, $this->_getMaximumMessageLength());
		}
	}
	
	
	/**
	 * Returns the current progress value. For custom purposes.
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
			throw new Exception("The total value was not set for this progress bar. Please use the init(\$totalValue) method when initiating a progress bar.");
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
	 * Returns the limit of characters for a string next to a progressbar, depending on the available screen size.
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
	 * @return Int Number of available colums
	 */
	protected function _detectNumberOfTerminalColumns() {
		$terminalCols = null;
		try {
			exec('tput cols 2> /dev/null', $terminalCols, $errorCode);

			if ($errorCode == 0 && $terminalCols && array_key_exists(0, $terminalCols)) {
				return (int)$terminalCols[0];
			}
		} catch (Exception $e) {}
		
		return self::DEFAULT_SCREEN_SIZE;
	}
	
	
	protected function _getBarWidthByScreenSize() {
		if ($this->_columns < 40) {
			return 5;
		} elseif ($this->_columns < 70) {
			return 10;
		} elseif ($this->_columns < 80) {
			return 15;
		} else return 20;
	}	
}