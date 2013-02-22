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
	protected $_path;

	protected $_content;

	protected $_deployParams = array('server', 'deploy_to', 'user');


	public function __construct() {
		$this->_setPath();
		$this->_setContent();
	}


	/**
	 * Returns the deploy parameters for a specific environment.
	 *
	 * @param String $environment 	The environment to get parameters for (i.e. 'integration' or 'production').
	 * @return Array				List of deploy parameters:
	 * 									'server' 	=> 'myhost.example.com',
	 *									'deploy_to' => '/var/www/mylittlepony',
	 *									'user' 		=> 'SSH user'
	 */
	public function getParams($environment) {
		$output = array();
		$matches = array();
		$envContent = $this->getContent($environment);

		if (preg_match_all('/:?(?P<paramName>'. implode('|', $this->_deployParams) .'),? "(?P<paramValue>.*)"/', $envContent, $matches)) {
			foreach ($this->_deployParams as $p) {
				$index = array_search($p, $matches['paramName']);
				if ($index !== false) {
					$output[$p] = $matches['paramValue'][$index];
				} else throw new Exception("Did not find the configuration for {$p}, for environment {$environment} in {$this->_path}.");
			}
		} else throw new Exception("Could not extract deploy parameters for {$environment} from {$this->_path}.");

		return $output;
	}


	/**
	 * Returns the raw content of the Capistrano deploy configuration (in Ruby).
	 *
	 * @param String [$environment] Optional environment (i.e. 'integration' or 'production') to filter by.
	 */
	public function getContent($environment = null) {
		if ($environment) {
			$envEntryHead = "task :{$environment} do";
			if (preg_match("/\n[^#]{$envEntryHead}/", $this->_content)) {
				$envStart 		= strpos($this->_content, $envEntryHead) + strlen($envEntryHead);
				$envEnd 		= strpos($this->_content, "\nend", $envStart);
				$envContent 	= trim(substr($this->_content, $envStart, $envEnd - $envStart));

				return $envContent;
			} else {
				throw new Exception("Environment configuration for '{$environment}' not found in {$this->_path}.");
			}			
		} else return $this->_content;
	}


	protected function _setPath() {
		$this->_path = APPLICATION_PATH . DIRECTORY_SEPARATOR . 'configs' . DIRECTORY_SEPARATOR . 'deploy.rb';
	}


	protected function _setContent() {
		$this->_content = file_get_contents($this->_path);
	}
}