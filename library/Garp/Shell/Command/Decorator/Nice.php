<?php
/**
 * Garp_Shell_Command_Decorator_Nice
 * @author David Spreekmeester | Grrr.nl
 */
class Garp_Shell_Command_Decorator_Nice extends Garp_Shell_Command_Abstract {
	const COMMAND_PREFIX_NICE = 'nice -19 ';

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
		$prefix			= self::COMMAND_PREFIX_NICE;
		
		return $prefix . $commandString;
	}
	
}

