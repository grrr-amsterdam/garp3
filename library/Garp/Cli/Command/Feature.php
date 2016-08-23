<?php
/**
 * Garp_Cli_Command_Feature
 * Start and finish a new feature.
 * Basically a wrapper around git flow and semver.
 *
 * @package Garp_Cli_Command
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Cli_Command_Feature extends Garp_Cli_Command_Flow {

    public function start($args) {
        if (empty($args[0])) {
            Garp_Cli::errorOut(
                'No feature given. ' .
                'Do you want me to come up with a new feature myself?'
            );
            Garp_Cli::errorOut('(I suggest making me self-aware)');
            return false;
        }
        $feature = $args[0];
        if ($name = $this->_getAuthorName()) {
            $feature = $name . '-' . $feature;
        }
        $git_flow_feature_start_cmd = 'git flow feature start ' . $feature;
        $this->_exec_cmd($git_flow_feature_start_cmd);
        return true;
    }

    public function finish($args) {
        $branch = $this->_get_current_branch();
        $prefix = $this->_get_gitflow_prefix('feature');

        if (!preg_match('~^' . preg_quote($prefix) . '~', $branch)) {
            Garp_Cli::errorOut('You are not currently on a feature branch.');
            return false;
        }

        $curr_feature = preg_replace('~^' . preg_quote($prefix) . '~', '', $branch);
        $git_flow_feature_end_cmd = 'git flow feature finish ' . $curr_feature;
        passthru($git_flow_feature_end_cmd);
        return true;
    }

    public function publish($args) {
        $branch = $this->_get_current_branch();
        $prefix = $this->_get_gitflow_prefix('feature');

        if (!preg_match('~^' . preg_quote($prefix) . '~', $branch)) {
            Garp_Cli::errorOut('You are not currently on a feature branch.');
            return false;
        }

        $curr_feature = preg_replace('~^' . preg_quote($prefix) . '~', '', $branch);
        $git_flow_feature_publish_cmd = 'git flow feature publish ' . $curr_feature;
        passthru($git_flow_feature_publish_cmd);
        return true;
    }

    public function track($args) {
        if (empty($args)) {
            Garp_Cli::errorOut('No feature name provided.');
            return false;
        }
        $branch = $args[0];
        $git_flow_feature_publish_cmd = 'git flow feature track ' . $branch;
        passthru($git_flow_feature_publish_cmd);
        return true;
    }

    protected function _getAuthorName() {
        $name = $this->_exec_cmd("git config user.name");
        if (!$name) {
            return '';
        }
        $name = trim($name);
        $name = explode(' ', $name);
        return strtolower(Garp_Util_String::toDashed($name[0]));
    }

    public function help() {
        Garp_Cli::lineOut('Usage:');
        Garp_Cli::lineOut(' g feature start discombobulator', Garp_Cli::BLUE);
        Garp_Cli::lineOut(' g feature finish', Garp_Cli::BLUE);
        Garp_Cli::lineOut(' g feature publish', Garp_Cli::BLUE);
        Garp_Cli::lineOut(' g feature track discombobulator', Garp_Cli::BLUE);
        Garp_Cli::lineOut('Note: this requires the git flow and semver commandline utilities.');
    }

}
