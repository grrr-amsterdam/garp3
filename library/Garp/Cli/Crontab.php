<?php
/**
 * Garp_Cli_Crontab
 * Manages crontab files
 *
 * @package Garp_Cli
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Cli_Crontab {
    /**
     * Class constructor
     *
     * @return void
     */
    public function __construct() {
    }

    /**
     * Fetch current jobs
     *
     * @return array
     */
    public function fetchAll() {
        $crontab = $this->_exec('crontab -l');
        $crontab = trim($crontab);
        if (preg_match('/no crontab for/', $crontab)) {
            return null;
        } else {
            $cronjobs = explode("\n", $crontab);
            return $cronjobs;
        }
    }

    /**
     * Execute command lines
     *
     * @param string $command
     * @return string
     */
    protected function _exec($command) {
        return shell_exec($command);
    }
}
