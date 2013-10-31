<?php
/**
 * Garp_Cli_Command_Cluster
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cli
 * @lastmodified $Date: $
 */
class Garp_Cli_Command_Cluster extends Garp_Cli_Command {
	/**
	 * Central start method
	 * @param Array $args array('run')
	 * @return Void
	 */
	public function main(array $args = array()) {
		if (empty($args)) {
			Garp_Cli::lineOut($this->_getHelpText());
		} else {
			if (!array_key_exists(0, $args)) {
				Garp_Cli::lineOut("Please indicate what you want me to do.\n");
				$this->_exit($this->_getHelpText());
			} else {
				$command = $args[0];
			
				if (method_exists($this, '_'.$command)) {
					$this->{'_'.$command}($args);
				} else {
					$this->_exit("Sorry, I don't know the command '{$command}'.\n\n".$this->_getHelpText());
				}
			}
		}
	}


	protected function _getHelpText() {
		return "Usage:\n"
			."  garp.php Cluster run\n\n"
			."  From a cronjob, APPLICATION_ENV needs to be set explicitly:\n"
			."  garp.php Cluster run --APPLICATION_ENV=development";
	}
	
	
	protected function _run($args) {
		$clusterServerModel = new Model_ClusterServer();
		list($serverId, $lastCheckIn) = $clusterServerModel->checkIn();

		$this->_runCacheClearJobs($serverId, $lastCheckIn);
		$this->_runRecurringJobs($serverId, $lastCheckIn);
	}


	/**
	 * @param Int $serverId Database id of the current server in the cluster
	 * @param String $lastCheckIn MySQL datetime that represents the last check-in time of this server
	 */
	protected function _runCacheClearJobs($serverId, $lastCheckIn) {
		try {
			$cluster = new Garp_Cache_Store_Cluster();
			$cluster->executeDueJobs($serverId, $lastCheckIn);
		} catch (Exception $e) {
			throw new Exception('Error during execution of cluster clear job. '.$e->getMessage());
		}
		
		if (is_array($cluster->clearedTags)) {
			if (empty($cluster->clearedTags))
				Garp_Cli::lineOut('Clustered cache purged for all models.');
			else
				Garp_Cli::lineOut('Clustered cache purged for models '.implode(', ', $cluster->clearedTags));
		} elseif (is_bool($cluster->clearedTags) && !$cluster->clearedTags) {
			Garp_Cli::lineOut('No clustered cache purge jobs to run.');
		} else {
			throw new Exception("Error in clearing clustered cache.");
		}
	}
	

	/**
	 * @param Int $serverId Database id of the current server in the cluster
	 * @param String $lastCheckIn MySQL datetime that represents the last check-in time of this server
	 */
	protected function _runRecurringJobs($serverId, $lastCheckIn) {
		$recurringJobModel = new Model_ClusterRecurringJob();
		$jobs = $recurringJobModel->fetchDue($serverId, $lastCheckIn);
		$loader = Garp_Loader::getInstance(array('paths' => array()));

		foreach ($jobs as $job) {
			$commandParts = explode(' ', $job->command);
			
			
			$class = $commandParts[0];
			$method = $commandParts[1];
			$argumentsIn = array_slice($commandParts, 2);
			$argumentsOut = array();

			foreach ($argumentsIn as $argument) {
				if (strpos($argument, '=') === false) {
					$argumentsOut[] = $argument;
				} else {
					$argumentParts = explode('=', $argument);
					$argumentName = substr($argumentParts[0], 2);
					$argumentValue = $argumentParts[1];
					$argumentsOut[$argumentName] = $argumentValue;
				}
			}

			$fullClassNameWithoutModule = 'Cli_Command_' . $class;
			$appClassName = 'App_' . $fullClassNameWithoutModule;
			$garpClassName = 'Garp_' . $fullClassNameWithoutModule;

			if ($loader->isLoadable($appClassName)) {
				$className = $appClassName;
			} elseif ($loader->isLoadable($garpClassName)) {
				$className = $garpClassName;
			} else {
				throw new Exception("Cannot load {$appClassName} or {$garpClassName}.");
			}

			$acceptMsg = 'Accepting job: ' . $className . '.' . $method;
			if ($argumentsOut) {
				$acceptMsg .= ' with arguments: ' . str_replace(array("\n", "\t", "  "), '', print_r($argumentsOut, true));
			}
			Garp_Cli::lineOut($acceptMsg);

			$recurringJobModel->accept($job->id, $serverId);

			$class = new $className();
			$class->{$method}($argumentsOut);
		}
		
		if (!count($jobs)) {
			Garp_Cli::lineOut('No recurring jobs to run.');
		}
	}


	protected function _exit($msg) {
		Garp_Cli::lineOut($msg);
		exit;
	}
}
