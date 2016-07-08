<?php
/**
 * Garp_Cli_Command_Capistrano
 * Updates the Capistrano settings for this project.
 *
 * @package Garp_Cli_Command
 * @author David Spreekmeester <david@grrr.nl>
 */
class Garp_Cli_Command_Capistrano extends Garp_Cli_Command {
    const CAPFILE_REPO_URL = "https://github.com/grrr-amsterdam/garp_scaffold/trunk/Capfile";

    /**
     * Central start method
     *
     * @param Array $args Various options. Must contain;
     * @return Boolean
     */
    public function main(array $args = array()) {
        if ($this->_helpWasRequested($args)) {
            $this->_help();
            return;
        }

        return $this->_update();
    }

    protected function _update() {
        Garp_Cli::lineOut('Current Capistrano setup of this project:');
        $version = $this->_getCurrentCapVersion();
        if (!$version) {
            Garp_Cli::errorOut('No Capistrano setup found in ' . getcwd());
            return false;
        }

        if ($version === 3) {
            Garp_Cli::errorOut('Setup is already suited for Capistrano 3.');
            return false;
        }

        if ($version === 2) {
            Garp_Cli::lineOut('Capistrano 2 setup found, attempting update.');
            return $this->_updateVersionSteps();
        }
        return true;
    }

    protected function _getCurrentCapVersion() {
        $capfile = 'Capfile';

        if (!file_exists($capfile)) {
            return false;
        }

        $capContents = file_get_contents($capfile);
        return strpos($capContents, 'set :deploy_config_path') !== false
            ? 3
            : 2
        ;
    }

    protected function _updateVersionSteps() {
        $this->_replaceCapFile();
        $this->_restructureDeployRb();
        $this->_replaceSharedCachePathConfig();
        $this->_replaceSharedUploadPathConfig();
        $this->_removeSharedUploadSymlink();
        return true;
    }

    protected function _replaceSharedCachePathConfig() {
        $path = 'application/configs/';
        $files = array('application.ini', 'cache.ini');

        foreach ($files as $file) {
            if (!file_exists($path . $file)) {
                continue;
            }

            $content = file_get_contents($path . $file);

            $baseLine
                = 'resources.cacheManager.pagetag.backend.options.cache_dir = APPLICATION_PATH "';
            $oldLine = $baseLine . '/../../../shared/tags"';
            $newLine = $baseLine . '/data/cache/tags"';
            $content = str_replace($oldLine, $newLine, $content);
            file_put_contents($path . $file, $content);
        }
    }

    protected function _replaceSharedUploadPathConfig() {
        $path = 'application/configs/';
        $files = array('application.ini', 'assets.ini');

        foreach ($files as $file) {
            if (!file_exists($path . $file)) {
                continue;
            }

            $content = file_get_contents($path . $file);
            $content = str_replace('/uploads/shared/images', '/uploads/images', $content);
            $content = str_replace('/uploads/shared/documents', '/uploads/documents', $content);
            file_put_contents($path . $file, $content);
        }
    }

    protected function _removeSharedUploadSymlink() {
        $path = 'public/uploads/shared';
        // file_exists() doesn't work on symlinks that point to a non-existing dir
        @unlink($path);
    }

    protected function _replaceCapFile() {
        $command = "svn export " . self::CAPFILE_REPO_URL . " --force";
        `$command`;
    }

    protected function _restructureDeployRb() {
        $deployRbPath = 'application/configs/deploy.rb';
        $oldDeployRb = file_get_contents($deployRbPath);

        $appSpecificHook = $this->_getAppHookPlaceholder();

        $envConfigStart     = strpos($oldDeployRb, 'task :');
        $envConfigs         = substr($oldDeployRb, $envConfigStart);
        $newDeployRb        = substr($oldDeployRb, 0, $envConfigStart);
        $newDeployRb        = str_replace(':repository', ':repo_url', $newDeployRb);
        $newDeployRb        .= $appSpecificHook;

        file_put_contents($deployRbPath, $newDeployRb);

        @mkdir('application/configs/deploy');

        $envStrings = preg_split("/\s*end\s*/", $envConfigs, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($envStrings as $envString) {
            if (substr($envString, 0, 1) === '#') {
                continue;
            }

            $envName = $this->_extractTaskName($envString);
            if (!$envName) {
                continue;
            }

            $config = array(
                'name' => $envName,
                'server' => $this->_extractServer($envString)
            );

            $params = array('deploy_to', 'user', 'garp_env', 'branch');
            foreach ($params as $param) {
                $config[$param] = $this->_extractRubySymbol($envString, $param);
            }

            $this->_writeDeployConfigNewStyle($config);
        }
    }

    protected function _writeDeployConfigNewStyle(array $config) {
        $configOutput = <<<EOF
server '{$config['server']}', user: '{$config['user']}', roles: %w{web app}
set :deploy_to, "{$config['deploy_to']}"
set :garp_env, "{$config['garp_env']}"
EOF;

        if ($config['branch']) {
            $configOutput .= "\nset :branch, '{$config['branch']}'";
        }

        file_put_contents('application/configs/deploy/' . $config['name'] . '.rb', $configOutput);
    }

    protected function _extractTaskName($haystack) {
        $pattern = '/task :(\w+)/';
        return $this->_extractAbstract($pattern, $haystack);
    }

    protected function _extractServer($haystack) {
        $pattern = "/server \"([\w\.-]+)\"/";
        return $this->_extractAbstract($pattern, $haystack);
    }

    protected function _extractRubySymbol($haystack, $varName) {
        $pattern = "/:{$varName}, \"([\w\/\.]+)\"/";
        return $this->_extractAbstract($pattern, $haystack);
    }

    protected function _extractAbstract($pattern, $haystack) {
        $needle = preg_match($pattern, $haystack, $matches);
        if (array_key_exists(1, $matches)) {
            return $matches[1];
        }
    }

    protected function _getAppHookPlaceholder() {
        return <<<EOF
task :started do
    on roles(:web) do
        info "No app-specific startup deploy tasks in this project."
    end
end

task :updated do
    on roles(:web) do
        info "No app-specific after-update deploy tasks in this project."
    end
end
EOF;
    }

    /**
     * Help
     *
     * @return Boolean
     */
    protected function _help() {
        Garp_Cli::lineOut('Usage:');
        Garp_Cli::lineOut(' g capistrano', Garp_Cli::BLUE);
    }

    protected function _helpWasRequested(array $args) {
        return
            array_key_exists(0, $args) &&
            strcasecmp($args[0], 'help') === 0
        ;
    }
}
