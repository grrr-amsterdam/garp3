<?php
/**
 * Garp_Cli_Command_Elasticsearch
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cli
 * @lastmodified $Date: $
 */
class Garp_Cli_Command_Elasticsearch extends Garp_Cli_Command {
	/**
	 * @var Garp_Service_Elasticsearch $_service
	 */
	protected $_service;
	

	public function main(array $args = array()) {
		$this->setService($this->_initService());
		parent::main($args);
	}

	public function prepare() {
		Garp_Cli::lineOut('Preparing Elasticsearch index...');
		
		$service 	= $this->getService();
		$log 		= $service->prepare();

		Garp_Cli::lineOut($log, Garp_Cli::BLUE);
	}

	public function help() {
		Garp_Cli::lineOut('# Usage');
		Garp_Cli::lineOut('Create a new index:');
		Garp_Cli::lineOut('  g elasticsearch prepare', Garp_Cli::BLUE);
		Garp_Cli::lineOut('');
	}

	/**
	 * @return Garp_Service_Elasticsearch
	 */
	public function getService() {
		return $this->_service;
	}
	
	/**
	 * @param Garp_Service_Elasticsearch $service
	 */
	public function setService(Garp_Service_Elasticsearch $service) {
		$this->_service = $service;
		return $this;
	}

	protected function _initService() {
		$service = new Garp_Service_Elasticsearch();
		return $service;
	}
}
