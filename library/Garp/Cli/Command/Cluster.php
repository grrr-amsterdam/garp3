<?php
/**
 * Garp_Cli_Command_Cluster
 *
 * @package Garp_Cli_Command
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Cli_Command_Cluster extends Garp_Cli_Command {

    public function run() {
        $clusterServerModel = new Model_ClusterServer();
        list($serverId, $lastCheckIn) = $clusterServerModel->checkIn();

        $this->_runCacheClearJobs($serverId, $lastCheckIn);
        $this->_runScheduledJobs($serverId, $lastCheckIn);
        $this->_runRecurringJobs($serverId, $lastCheckIn);
        return true;
    }

    public function clean() {
        $jobModel = new Model_ClusterClearCacheJob();
        $amount = $jobModel->deleteOld();
        Garp_Cli::lineOut('Done.');
        Garp_Cli::lineOut('Removed ' . number_format($amount, 0) . ' records.');
        return true;
    }

    public function help() {
        Garp_Cli::lineOut('Usage:');
        Garp_Cli::lineOut(' g Cluster run', Garp_Cli::BLUE);
        Garp_Cli::lineOut('');
        Garp_Cli::lineOut('Clean old job records:');
        Garp_Cli::lineOut(' g Cluster clean', Garp_Cli::BLUE);
        Garp_Cli::lineOut('');
        Garp_Cli::lineOut('From a cronjob, APPLICATION_ENV needs to be set explicitly:');
        Garp_Cli::lineOut(' g Cluster run --APPLICATION_ENV=development', Garp_Cli::BLUE);
        Garp_Cli::lineOut('');
        return true;
    }

    /**
     * @param Int $serverId Database id of the current server in the cluster
     * @param String $lastCheckIn MySQL datetime that represents
     *                            the last check-in time of this server
     * @return void
     */
    protected function _runCacheClearJobs($serverId, $lastCheckIn) {
        try {
            $cluster = new Garp_Cache_Store_Cluster();
            $cluster->executeDueJobs($serverId, $lastCheckIn);
        } catch (Exception $e) {
            throw new Exception('Error during execution of cluster clear job. ' . $e->getMessage());
        }

        if (is_array($cluster->clearedTags)) {
            if (empty($cluster->clearedTags)) {
                Garp_Cli::lineOut('Clustered cache purged for all models.');
            } else {
                Garp_Cli::lineOut(
                    'Clustered cache purged for models ' .
                    implode(', ', $cluster->clearedTags)
                );
            }
        } elseif (is_bool($cluster->clearedTags) && !$cluster->clearedTags) {
            Garp_Cli::lineOut('No clustered cache purge jobs to run.');
        } else {
            throw new Exception("Error in clearing clustered cache.");
        }
    }


    /**
     * @param Int $serverId Database id of the current server in the cluster
     * @param String $lastCheckIn MySQL datetime that
     *                            represents the last check-in time of this server
     * @return void
     */
    protected function _runRecurringJobs($serverId, $lastCheckIn) {
        $recurringJobModel = new Model_ClusterRecurringJob();
        $jobs = $recurringJobModel->fetchDue($serverId, $lastCheckIn);
        $this->_executeJobs($jobs, $serverId);

        if (!count($jobs)) {
            Garp_Cli::lineOut('No recurring jobs to run.');
        }
    }

    protected function _runScheduledJobs($serverId, $lastCheckIn) {
        // Make sure the model exists
        if (!class_exists('Model_ScheduledJob')) {
            return;
        }
        $scheduledJobModel = new Model_ScheduledJob();
        $jobs = $scheduledJobModel->fetchDue($serverId, $lastCheckIn);
        $this->_executeJobs($jobs, $serverId);

        if (!count($jobs)) {
            Garp_Cli::lineOut('No scheduled jobs to run.');
        }
    }

    protected function _executeJobs(Garp_Db_Table_Rowset $jobs, $serverId) {
        foreach ($jobs as $job) {
            $this->_executeJob($job, $serverId);
        }
    }

    protected function _executeJob(Garp_Db_Table_Row $job, $serverId) {
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

        if (class_exists($appClassName)) {
            $className = $appClassName;
        } elseif (class_exists($garpClassName)) {
            $className = $garpClassName;
        } else {
            throw new Exception("Cannot load {$appClassName} or {$garpClassName}.");
        }

        $acceptMsg = 'Accepting job: ' . $className . '.' . $method;
        if ($argumentsOut) {
            $acceptMsg .= ' with arguments: ' .
                str_replace(array("\n", "\t", "  "), '', print_r($argumentsOut, true));
        }
        Garp_Cli::lineOut($acceptMsg);

        // Update the job with acceptance data
        $job->accepter_id = $serverId;
        $job->last_accepted_at = date('Y-m-d H:i:s');
        $job->save();

        // Execute the command
        $class = new $className();
        $class->{$method}($argumentsOut);
    }

}
