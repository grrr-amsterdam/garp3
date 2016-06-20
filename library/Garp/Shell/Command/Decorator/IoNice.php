<?php
/**
 * Garp_Shell_Command_Decorator_IoNice
 * @author David Spreekmeester | Grrr.nl
 *
 * Example of usage:
 * $command = new Garp_Shell_Command_Decorator_Nice($command);
 * $ioNiceCommand = new Garp_Shell_Command_IoNiceIsAvailable();
 * $ioNiceIsAvailable = $ioNiceCommand->executeLocally();
 *
 * if ($ioNiceIsAvailable) {
 *      $command = new Garp_Shell_Command_Decorator_IoNice($command);
 * }
 */
class Garp_Shell_Command_Decorator_IoNice implements Garp_Shell_Command_Protocol {
    const COMMAND_PREFIX_IONICE = 'ionice -c3 ';

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
        $command        = $this->getCommand();
        $commandString  = $command->render();
        $prefix         = self::COMMAND_PREFIX_IONICE;

        return $prefix . $commandString;
    }

}

