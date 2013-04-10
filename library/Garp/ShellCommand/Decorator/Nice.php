<?php
/**
 * Garp_ShellCommand_Decorator_Nice
 * @author David Spreekmeester | Grrr.nl
 */
class Garp_ShellCommand_Decorator_Nice implements Garp_ShellCommand_Protocol {
	const COMMAND_PREFIX_NICE = 'nice -19 ';

	/**
	 * @var Garp_ShellCommand_Protocol $_command
	 */
	protected $_command;


	public function __construct(Garp_ShellCommand_Protocol $command) {
		$this->setCommand($command);
	}
	
	/**
	 * @return Garp_ShellCommand_Protocol
	 */
	public function getCommand() {
		return $this->_command;
	}
	
	/**
	 * @param Garp_ShellCommand_Protocol $command
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

