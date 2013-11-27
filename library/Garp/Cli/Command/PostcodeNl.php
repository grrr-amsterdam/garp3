
<?php
/**
 * Garp_Cli_Command_PostcodeNl
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cli
 * @lastmodified $Date: $
 */
class Garp_Cli_Command_PostcodeNl extends Garp_Cli_Command {
	const ERROR_NO_FILE_PROVIDED =
		"No path provided to the 6PP CSV file from postcode.nl";

	protected $_args;


	/**
	 * Central start method
	 * @return Void
	 */
	public function main(array $args = array()) {
		if (!$args) {
			$this->_displayHelp();
			return;
		}

		$this->_args = $args;

		$command = $args[0];
		$this->{'_' . $command}();
	}

	protected function _import() {
		$this->_obligateFileParam();

		$path = $this->_args[1];
		$content = file_get_contents($path);

		$size = round(strlen($content) / 1024 / 1024);
		Garp_Cli::lineOut("Thanks for feeding me {$size} MB of data.");

		$this->_parse($content);
	}

	/**
 	 * @param String $content The content of the CSV 6PP file
 	 */
	protected function _parse($content) {
		$response = new Garp_Service_PostcodeNl_Response($content);
		$linesCountLabel = number_format(count($response), 0, ',', '.'); 
		Garp_Cli::lineOut("Parsing {$linesCountLabel} lines.");

		return $response;
	}

	protected function _obligateFileParam() {
		if (!array_key_exists(1, $this->_args)) {
			$this->_printError(self::ERROR_NO_FILE_PROVIDED);
			$this->_displayHelp();
			exit;
		}
	}

	protected function _printError($message) {
		Garp_Cli::errorOut($message);
		Garp_Cli::lineOut("");
	}

	protected function _displayHelp() {
		Garp_Cli::lineOut("Commands");
		Garp_Cli::lineOut("g postcodenl import ~/pcdata.csv", Garp_Cli::BLUE);
		Garp_Cli::lineOut("Where the last argument is the path to the 6PP CSV file from postcode.nl.");

	}
}
