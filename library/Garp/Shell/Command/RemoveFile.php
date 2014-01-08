<?php
/**
 * Garp_Shell_Command_RemoveFile
 * @author David Spreekmeester | Grrr.nl
 */
class Garp_Shell_Command_RemoveFile extends Garp_Shell_Command_Abstract {
	const COMMAND_REMOVE = 'rm -f %s';

	/**
	 * @var String $_file
	 */
	protected $_file;
		

	/**
	 * @param String $file Absolute path to the file that should be removed.
	 */
	public function __construct($file) {
		$this->setFile($file);
	}

	/**
	 * @return String Absolute path to the file that should be removed.
	 */
	public function getFile() {
		return $this->_file;
	}
	
	/**
	 * @param String $file Absolute path to the file that should be removed.
	 */
	public function setFile($file) {
		$this->_file = $file;
	}

	public function render() {
		$file 		= $this->getFile();

		$dumpCommand = sprintf(
			self::COMMAND_REMOVE,
			$file
		);

		return $dumpCommand;
	}
}