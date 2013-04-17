<?php
/**
 * Garp_Shell_Command_Abstract
 * Foundation for Shell_Command that automatically includes decorators and can run commands.
 * @author David Spreekmeester | Grrr.nl
 */
abstract class Garp_Shell_Command_Abstract implements Garp_Shell_Command_Protocol {
	/**
	 * @var Bool $_isThrottled Whether running this command should be throttled on server load.
	 */
	protected $_isThrottled = false;
	

	/**
	 * Executes the command in a local shell.
	 * @return String The command's output
	 */
	public function executeLocally() {
		$commandString 	= $this->renderThrottledCommandIfNecessary();
		return $this->_executeStringLocally($commandString);
	}
	
	/**
	 * Executes the command through an SSH session in a remote terminal.
	 * @return String The command's output
	 */
	public function executeRemotely(Garp_Shell_RemoteSession $session) {
		$commandString	= $this->renderThrottledCommandIfNecessary($session);
		return $this->_executeStringRemotely($commandString, $session);
	}

	/**
	 * Executes provided command string through an SSH session in a remote terminal.
	 * @return String The command's output
	 */
	protected function _executeStringRemotely($commandString, Garp_Shell_RemoteSession $session) {
		if ($stream = ssh2_exec($session->getSshSession(), $commandString)) {
			stream_set_blocking($stream, true);
			$content = stream_get_contents($stream);		
			$this->_bubbleSshErrors($stream);
			fclose($stream);

			return $content;
		}

		return false;		
	}
	
	protected function _executeStringLocally($commandString) {
		$output 		= null;

		exec($commandString, $output);
		$output = implode("\n", $output);
		return $output;		
	}
	
	public function renderThrottledCommandIfNecessary() {
		if ($this->isThrottled()) {
			return $this->renderThrottledCommand();
		}
		
		return $this->render();
	}
	
	
	/**
	 * @return Bool
	 */
	public function isThrottled() {
		return $this->_isThrottled;
	}
	
	/**
	 * Returns the decorated version of this command, for throttling purposes.
	 * @param Garp_Shell_RemoteSession $session Pass the session instance along if this is a remote server.
	 * @return Garp_Shell_Command_Abstract
	 */
	public function renderThrottledCommand(Garp_Shell_RemoteSession $session = null) {
		$command = new Garp_Shell_Command_Decorator_Nice($this);

		$ioNiceCommand = new Garp_Shell_Command_IoNiceIsAvailable();
		$ioNiceCommandString = $ioNiceCommand->render();
		
		$ioNiceIsAvailable = $session ?
			$this->_executeStringRemotely($ioNiceCommandString, $session) :
			$this->_executeStringLocally($ioNiceCommandString)
		;

		if ($ioNiceIsAvailable) {
			$command = new Garp_Shell_Command_Decorator_IoNice($command);
		}

		return $command->render();
	}


	protected function _bubbleSshErrors($sshStream) {
		$errorStream = ssh2_fetch_stream($sshStream, SSH2_STREAM_STDERR);
		stream_set_blocking($errorStream, true);
		$error = stream_get_contents($errorStream);
		fclose($errorStream);

		if ($error) {
			throw new Exception($error);
		}
	}
	
}