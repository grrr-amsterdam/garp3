<?php
/**
 * Garp_Content_Db_ShellCommand_Decorator_IoNice
 * @author David Spreekmeester | Grrr.nl
 */
class Garp_Content_Db_ShellCommand_Decorator_IoNice implements Garp_Content_Db_ShellCommand_Protocol {
	const COMMAND_PREFIX_IONICE = 'ionice -c3 ';

	/**
	 * @var Garp_Content_Db_ShellCommand_Protocol $_command
	 */
	protected $_command;


	public function __construct(Garp_Content_Db_ShellCommand_Protocol $command) {
		$this->setCommand($command);
	}
	
	/**
	 * @return Garp_Content_Db_ShellCommand_Protocol
	 */
	public function getCommand() {
		return $this->_command;
	}
	
	/**
	 * @param Garp_Content_Db_ShellCommand_Protocol $command
	 */
	public function setCommand($command) {
		$this->_command = $command;
	}


	public function render() {
		$command 		= $this->getCommand();
		$commandString 	= $command->render();
		$prefix			= self::COMMAND_PREFIX_IONICE;
		
		return $prefix . $commandString;
	}
	
}

