<?php
/**
 * Garp_Deploy_Config
 * Represents a (Capistrano) deploy configuration.
 * 
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
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
	 * Returns the deploy parameters for a specific environment.
	 *
	 * @param String $env The environment to get parameters for
	 *					(i.e. 'integration' or 'production').
	 * @return Array List of deploy parameters:
	 * 					'server' 	=> 'myhost.example.com',
	 *					'deploy_to' => '/var/www/mylittlepony',
	 *					'user' 		=> 'SSH user'
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
	 *					(i.e. 'integration' or 'production').
	 * @return String Name of deploy parameter:
	 * 					f.i. 'application', 'server',
	 * 					'deploy_to' or 'user'.
	 */
	public function getParam($env, $param) {
		$params = $this->getParams($env);
		return $params[$param];
	}

	/**
 	 * Parses the generic configuration.
 	 * @param String $content
 	 * @return Array
 	 */
	protected function _parseContent($content) {
		$output = array();
		$matches = array();
		$paramsString = implode('|', $this->_deployParams);
		$pattern = '/:?(?P<paramName>'. $paramsString
			.')[,:]? [\'"](?P<paramValue>[^\'"]*)[\'"]/';

		if (!preg_match_all($pattern, $content, $matches)) {
			throw new Exception(
				"Could not extract deploy parameters from "
				. self::GENERIC_CONFIG_PATH
			);
		}

		foreach ($this->_deployParams as $p) {
			$index = array_search($p, $matches['paramName']);
			if ($index !== false) {
				$output[$p] = $matches['paramValue'][$index];
			}
		}

		return $output;
	}

	
	/**
	 * Returns the raw content of the Capistrano
	 * deploy configuration (in Ruby) per environment.
	 *
	 * @param String $env Environment (i.e. 'integration'
	 * or 'production') of which to retrieve config params.
	 */
	protected function _fetchEnvContent($env) {
		$envPath = BASE_PATH . self::ENV_CONFIG_PATH . $env . '.rb';
		$envConfig = file_get_contents($envPath);

		if ($envConfig === false) {
			throw new Exception(
				"Could not read the configuration file for "
				. "the '{$env}' environment."
			);
		}

		return $envConfig;
	}
	
	protected function _fetchGenericContent() {
		return file_get_contents(BASE_PATH . self::GENERIC_CONFIG_PATH);
	}
}
