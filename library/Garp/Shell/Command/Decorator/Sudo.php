<?php
/**
 * Garp_Shell_Command_Decorator_Sudo
 * @author David Spreekmeester | Grrr.nl
 */
class Garp_Shell_Command_Decorator_Sudo extends Garp_Shell_Command_Abstract {
	const COMMAND_PREFIX = '/usr/bin/sudo ';

	/**
	 * @var Garp_Shell_Command_Protocol $_command
	 */
	protected $_command;


	public function __construct(Garp_Shell_Command_Protocol $command) {
		$this->setCommand($command);
	}
	
	/**
	 * @return Garp_Shell_Command_Protocol
	 */
	public function getCommand() {
		return $this->_command;
	}
	
	/**
	 * @param Garp_Shell_Command_Protocol $command
	 */
	public function setCommand($command) {
		$this->_command = $command;
	}

	public function render() {
		$command 		= $this->getCommand();
		$commandString 	= $command->render();

		if ($this->_isPipedEchoCommand($commandString)) {
			return $this->_prefixPipedEchoCommand($commandString);
		}
		
		return self::COMMAND_PREFIX . $commandString;
	}
	
	protected function _isPipedEchoCommand($commandString) {
		return strpos($commandString, '|') && substr($commandString, 0, 5) === 'echo ';
	}
	
	protected function _prefixPipedEchoCommand($commandString) {
		$parts 			= explode('|', $commandString);
		$parts[1] 		= self::COMMAND_PREFIX . $parts[1];
		$commandString 	= implode('|', $parts);
		
		return $commandString;
	}
}