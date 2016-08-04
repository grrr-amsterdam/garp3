<?php
/**
 * Garp_Cli_Command_Git
 * Providing a couple of Git shortcuts.
 * g git setup sets sensible upstreams for git flow related branches.
 *
 * @package Garp_Cli_Command
 * @author Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Cli_Command_Git extends Garp_Cli_Command {
    /**
     * Setup Git as per Grrr conventions
     *
     * @return bool
     */
    public function setup() {
        Garp_Cli::lineOut('Configuring Git...');
        // Configure core.fileMode
        passthru('git config core.fileMode false');

        // Configure color.ui
        passthru('git config color.ui auto');

        // Make sure a master branch exists (git flow will create develop, but not master)
        if (!$this->_hasBranch('master')) {
            // create branch
            passthru('git branch master');
        }

        // Init Git Flow
        passthru('git flow init');

        // Set upstreams, only if they exist
        if ($this->_hasBranch('remotes/origin/master')) {
            passthru('git branch --set-upstream-to=origin/master master');
        }
        if ($this->_hasBranch('remotes/origin/develop')) {
            passthru('git branch --set-upstream-to=origin/develop develop');
        }

        Garp_Cli::lineOut('Done.');
        return true;
    }

    protected function _hasBranch($branch) {
        $branches = explode("\n", trim(`git branch -a`));
        $branches = array_map(
            function ($item) {
                return ltrim($item, '* ');
            },
            $branches
        );
        return in_array($branch, $branches);
    }

    /**
     * Help
     *
     * @return bool
     */
    public function help() {
        Garp_Cli::lineOut('Usage:');
        Garp_Cli::lineOut('Setup Git environment');
        Garp_Cli::lineOut('  g Git setup', Garp_Cli::BLUE);
        Garp_Cli::lineOut('');
        return true;
    }

}

