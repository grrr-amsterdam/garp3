<?php

use Garp\Functional as f;

/**
 * Garp_Deploy_Config
 * Represents a (Capistrano) deploy configuration.
 *
 * @package Garp
 * @subpackage Deploy
 * @author David Spreekmeester <david@grrr.nl>
 */
class Garp_Deploy_Config {
    const GENERIC_CONFIG_PATH = '/application/configs/deploy.rb';
    const ENV_CONFIG_PATH = '/application/configs/deploy/';

    protected $_genericContent;

    protected $_deployParams = array(
        'server', 'deploy_to', 'user', 'application', 'repo_url', 'branch'
    );

    public function __construct() {
        $this->_genericContent = $this->_fetchGenericContent();
    }

    /**
     * Returns whether the deployment setup is configured
     * for this environmentparameters for a specific environment.
     *
     * @param String $env The environment to get parameters for
     *                  (i.e. 'integration' or 'production').
     * @return Bool Whether the deploy configuration is set.
     */
    public function isConfigured($env) {
        if (!file_exists($this->_createPathFromEnv($env))) {
            return false;
        }
        return !!$this->_fetchEnvContent($env);
    }

    /**
     * Returns the deploy parameters for a specific environment.
     *
     * @param String $env The environment to get parameters for
     *                  (i.e. 'integration' or 'production').
     * @return Array List of deploy parameters:
     *                  'server'    => 'myhost.example.com',
     *                  'deploy_to' => '/var/www/mylittlepony',
     *                  'user'      => 'SSH user'
     */
    public function getParams($env) {
        $genericParams = $this->_parseContent($this->_genericContent);
        $envParams = $this->_parseContent($this->_fetchEnvContent($env));
        $output = $genericParams + $envParams;

        return $output;
    }

    /**
     * Returns a deploy parameter for a specific environment.
     *
     * @param String $env The environment to get parameters for
     *                  (i.e. 'integration' or 'production').
     * @param String $param The parameter name being requested.
     * @return String Name of deploy parameter:
     *                  f.i. 'application', 'server',
     *                  'deploy_to' or 'user'.
     */
    public function getParam($env, $param) {
        $params = $this->getParams($env);
        return $params[$param];
    }

    /**
     * Parses the generic configuration.
     *
     * @param String $content
     * @return Array
     */
    protected function _parseContent($content) {
        $output = array();
        $matches = array();
        $paramsString = implode('|', $this->_deployParams);
        $pattern = '/:?(?P<paramName>' . $paramsString
            . ')[,:]? [\'"](?P<paramValue>[^\'"]*)[\'"]/';

        if (!preg_match_all($pattern, $content, $matches)) {
            throw new Exception(
                "Could not extract deploy parameters from "
                . self::GENERIC_CONFIG_PATH
            );
        }

        foreach ($this->_deployParams as $p) {
            $indices = array_keys(
                array_filter(
                    $matches['paramName'], function ($pn) use ($p) {
                        return $pn === $p;
                    }
                )
            );
            if (!count($indices)) {
                continue;
            }
            $output[$p] = array_values(f\pick($indices, $matches['paramValue']));

            // For now: only treat the server param as array (since it's common for it to be an
            // array, in the case of a multi-server setup)
            if ($p !== 'server') {
                $output[$p] = $output[$p][0];
            }
        }

        // explode server into user and server parts
        if (!empty($output['server'])) {
            $output['server'] = array_map(
                function ($serverConfig) {
                    $bits = explode('@', $serverConfig, 2);
                    return array(
                    'user' => $bits[0],
                    'server' => $bits[1]
                    );
                }, $output['server']
            );
        }

        return $output;
    }

    /**
     * Returns the raw content of the Capistrano
     * deploy configuration (in Ruby) per environment.
     *
     * @param String $env Environment (i.e. 'integration'
     * or 'production') of which to retrieve config params.
     * @return String The raw contents of the Capistrano
     * deploy configuration file.
     */
    protected function _fetchEnvContent($env) {
        $envPath = $this->_createPathFromEnv($env);
        $envConfig = file_get_contents($envPath);

        if ($envConfig === false) {
            throw new Exception(
                "Could not read the configuration file for "
                . "the '{$env}' environment."
            );
        }

        return $envConfig;
    }

    protected function _createPathFromEnv($env) {
        return BASE_PATH . self::ENV_CONFIG_PATH . $env . '.rb';
    }

    protected function _fetchGenericContent() {
        return file_get_contents(BASE_PATH . self::GENERIC_CONFIG_PATH);
    }
}

