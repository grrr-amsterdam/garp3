<?php
/**
 * Garp_Shell_Command_Decorator_Sudo
 * @author David Spreekmeester | Grrr.nl
 */
class Garp_Shell_Command_Decorator_Sudo extends Garp_Shell_Command_Abstract {
	const COMMAND_PREFIX = 'sudo ';

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
		$prefix			= self::COMMAND_PREFIX;
		
		return $prefix . $commandString;
	}
}