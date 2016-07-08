<?php
/**
 * Garp_Cli_Command_Flow
 * Git-flow shortcuts
 *
 * @package Garp_Cli_Command
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Cli_Command_Flow extends Garp_Cli_Command {

    /**
     * Cache git flow prefixes
     *
     * @var array
     */
    protected $_gitflow_prefixes = array();

    /**
     * Overwrite to perform sanity checks about wether we can actually execute `git flow`.
     *
     * @param array $args
     * @return bool
     */
    public function main(array $args = array()) {
        // Sanity check: do you have the right tools for the job?
        if (!$this->_required_tools_available()) {
            return false;
        }

        // Validate wether the git index is clean
        if (!$this->_validate_status()) {
            return false;
        }

        return parent::main($args);
    }

    /**
     * Execute the given command
     *
     * @param string $cmd
     * @return string
     */
    protected function _exec_cmd($cmd) {
        return shell_exec($cmd);
    }

    /**
     * Bump semver
     *
     * @param string $type Type of semver increment
     * @return void
     */
    protected function _bump_version($type) {
        // Init semver (if semver is already initialized it's no problem, just ignore the output)
        $this->_exec_cmd('semver init');

        $semver_cmd = "semver inc ";
        if (!in_array($type, array('patch', 'minor', 'major'))) {
            $semver_cmd .= "special ";
        }
        $semver_cmd .= $type;
        $this->_exec_cmd($semver_cmd);
    }

    /**
     * Get current semver
     *
     * @return string
     */
    protected function _get_current_version() {
        $version = $this->_exec_cmd('semver tag');
        $version = trim($version);
        return $version;
    }

    /**
     * Get current Git branch
     *
     * @return string
     */
    protected function _get_current_branch() {
        $branches = $this->_exec_cmd('git branch');
        $branches = explode("\n", $branches);
        $branches = preg_grep('/^\*/', $branches);
        if (!count($branches)) {
            return null;
        }
        // there can be only one
        $branch = current($branches);
        $branch = preg_replace('/^\*\s+/', '', $branch);
        $branch = trim($branch);
        return $branch;
    }

    protected function _validate_branch($type, $suffix) {
        $branch = $this->_get_current_branch();
        $prefix = $this->_get_gitflow_prefix($type);
        if ($branch == $prefix . $suffix) {
            return true;
        }
        Garp_Cli::errorOut("I'm sorry, you're not on the (right) $type branch.");
        Garp_Cli::lineOut("Expected branch: $prefix$suffix");
        Garp_Cli::lineOut("Got: $branch");
        return false;
    }

    /**
     * Get the configured Git-flow prefix
     *
     * @param string $category Git flow branch type
     * @return string
     */
    protected function _get_gitflow_prefix($category) {
        if (!isset($this->_gitflow_prefixes[$category])) {
            $prefix = $this->_exec_cmd("git config gitflow.prefix.$category");
            $prefix = trim($prefix);
            $this->_gitflow_prefixes[$category] = $prefix;
        }
        return $this->_gitflow_prefixes[$category];
    }

    /**
     * Check if git status is clean. Return boolean accordingly.
     *
     * @return bool
     */
    protected function _validate_status() {
        $st = $this->_exec_cmd('git status --porcelain');
        $st = trim($st);

        if (!$st) {
            return true;
        }

        Garp_Cli::errorOut("I can't proceed. Please clean your index first.");
        Garp_Cli::lineOut($st);
        return false;
    }

    /**
     * Check if semver and git flow are installed
     *
     * @return bool
     */
    protected function _required_tools_available() {
        $semver_checker = shell_exec('which semver');
        if (empty($semver_checker)) {
            Garp_Cli::errorOut('semver is not installed');
            Garp_Cli::lineOut('Install like this:');
            Garp_Cli::lineOut(' gem install semver', Garp_Cli::BLUE);
            return false;
        }
        /*
            @todo REFACTOR LATER! Check fails on Ubuntu?
        $gitflow_checker = shell_exec('which git-flow');
        if (empty($gitflow_checker)) {
            Garp_Cli::errorOut('git-flow is not installed');
            Garp_Cli::lineOut('Get it from brew');
            Garp_Cli::lineOut(' brew install git-flow', Garp_Cli::BLUE);
            return false;
        }
         */
        return true;
    }

}
