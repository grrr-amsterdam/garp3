<?php
/**
 * Garp_Cli_Command_Hotfix
 * Start and finish a new hotfix.
 * Basically a wrapper around git flow and semver.
 *
 * @package Garp_Cli_Command
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Cli_Command_Hotfix extends Garp_Cli_Command_Flow {

    /**
     * Start hotfix branch
     *
     * @param array $args
     * @return bool
     */
    public function start(array $args = array()) {
        $this->_bump_version('patch');
        $version = $this->_get_current_version();
        // Reset version cause we want to submit it only when finishing the hotfix
        $git_co_cmd = 'git checkout -- .semver';
        $this->_exec_cmd($git_co_cmd);

        $git_flow_start_release_cmd = 'git flow hotfix start ' . $version;
        $this->_exec_cmd($git_flow_start_release_cmd);

        $this->_bump_version('patch');

        // Add semver
        $git_add_cmd = 'git add .semver';
        $this->_exec_cmd($git_add_cmd);

        // Commit semver
        $git_ci_cmd  = 'git commit -m "Incremented version to ' . $version . '."';
        $this->_exec_cmd($git_ci_cmd);

        return true;
    }

    /**
     * Finish hotfix branch
     *
     * @param array $args
     * @return bool
     */
    public function finish(array $args = array()) {
        $version = $this->_get_current_version();
        if (!$this->_validate_branch('hotfix', $version)) {
            // When shit hits the fan: revert semver
            $git_co_cmd = 'git checkout -- .semver';
            $this->_exec_cmd($git_co_cmd);
            return false;
        }

        $finish_hotfix_cmd = 'git flow hotfix finish -m "Hotfix_' . $version . '" ' . $version;
        passthru($finish_hotfix_cmd);
        passthru('git push origin --tags');
        return true;
    }

    public function help() {
        Garp_Cli::lineOut('Usage:');
        Garp_Cli::lineOut(' g hotfix start', Garp_Cli::BLUE);
        Garp_Cli::lineOut(' g hotfix finish', Garp_Cli::BLUE);
        Garp_Cli::lineOut('');
        Garp_Cli::lineOut('Note: this requires the git flow and semver commandline utilities.');
    }

}
