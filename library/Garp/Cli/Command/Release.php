<?php
/**
 * Garp_Cli_Command_Release
 * Start and finish a new release.
 * Basically a wrapper around git flow and semver.
 *
 * @package Garp_Cli_Command_Release
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Cli_Command_Release extends Garp_Cli_Command_Flow {

    /**
     * Start a git flow release
     *
     * @param array $args
     * @return bool
     */
    public function start($args) {
        // Can be minor, major, or 'special'
        $type = isset($args[0]) ? $args[0] : 'minor';
        $this->_bump_version($type);
        $version = $this->_get_current_version();

        // Stash cause we can't start the release until the git index is clean
        $this->_exec_cmd('git stash');
        $this->_exec_cmd('git flow release start ' . $version);
        $this->_exec_cmd('git stash pop');
        $this->_exec_cmd('git add .semver');

        // Commit semver
        $this->_exec_cmd('git commit -m "Incremented version to ' . $version . '."');
        return true;
    }

    /**
     * Finish a git flow release
     *
     * @param array $args
     * @return bool
     */
    public function finish($args) {
        $version = $this->_get_current_version();
        if (!$this->_validate_branch('release', $version)) {
            return false;
        }
        passthru('git flow release finish -m "Release_' . $version . '" ' . $version);
        return true;
    }

    public function publish($args) {
        $version = $this->_get_current_version();
        if (!$this->_validate_branch('release', $version)) {
            return false;
        }

        passthru('git flow release publish ' . $version);
        return true;
    }

    public function track($args) {
        if (empty($args)) {
            Garp_Cli::errorOut('No release version provided.');
            return false;
        }
        $branch = $args[0];
        passthru('git flow release track ' . $branch);
        return true;
    }

    public function help() {
        Garp_Cli::lineOut('Usage:');
        Garp_Cli::lineOut(' g release start', Garp_Cli::BLUE);
        Garp_Cli::lineOut(' g release finish', Garp_Cli::BLUE);
        Garp_Cli::lineOut('');
        Garp_Cli::lineOut('Determine the type of version bump: (default is minor)');
        Garp_Cli::lineOut(' g release start major|minor', Garp_Cli::BLUE);
        Garp_Cli::lineOut('Or do a "special" release:');
        Garp_Cli::lineOut(' g release start "beta"');
        Garp_Cli::lineOut('');
        Garp_Cli::lineOut('Note: this requires the git flow and semver commandline utilities.');
    }

}
