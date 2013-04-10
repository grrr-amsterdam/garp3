<?php
/**
 * Garp_ShellCommand_RemoveFile
 * @author David Spreekmeester | Grrr.nl
 */
class Garp_ShellCommand_RemoveFile implements Garp_ShellCommand_Protocol {
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