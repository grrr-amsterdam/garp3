<?php
/**
 * Garp_Service_Vimeo_Pro_Method_Abstract
 * Vimeo Pro API wrapper around a specific method group.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Vimeo
 * @lastmodified $Date: $
 */
abstract class Garp_Service_Vimeo_Pro_Method_Abstract {
	/**
 	 * @var Garp_Service_Vimeo_Pro
 	 */
	protected $_service;


	/**
 	 * Class constructor
 	 * @param Garp_Service_Vimeo_Pro $service
 	 * @return Void
 	 */
	public function __construct(Garp_Service_Vimeo_Pro $service) {
		$this->setService($service);
	}


	/**
 	 * Set service
 	 * @param Garp_Service_Vimeo_Pro $service
 	 * @return $this
 	 */
	public function setService(Garp_Service_Vimeo_Pro $service) {
		$this->_service = $service;
		return $this;
	}


	/**
 	 * Get service
 	 * @return Garp_Service_Vimeo_Pro
 	 */
	public function getService() {
		return $this->_service;
	}


	/**
 	 * Make a request to the Vimeo service
 	 * @param String $method
 	 * @param Array $params
 	 * @return Mixed
 	 */
	public function request($method, array $params) {
		return $this->_service->request($method, $params);
	}
}
