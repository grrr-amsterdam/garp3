<?php
/**
 * @author David Spreekmeester | Grrr.nl
 *
 * # Usage
 * Call SOAP methods directly on this service:
 * $service = new Garp_Service_ActiveTickets('my-ActiveTickets-Clientname');
 * $response = $service->GetProgramListComplete(array('From' => '...', 'To' => '...', 'IncludePrices' => false));
 * You only have to provide the Clientname while constructing the service, not with every method.
 */
class Garp_Service_ActiveTickets {
	const WSDL = 
		"http://webservices.activetickets.com/members/ActiveTicketsMembersServices.asmx?WSDL";	
	const DATETIME_FORMAT = '%FT%T';

	protected $_username;

	protected $_clientOptions = array(
		'compression' => SOAP_COMPRESSION_ACCEPT
	);


	/**
 	 * @var Zend_Soap_Client $_client
 	 */
	protected $_client;

	
	public function __construct($username) {
		$this->_username = $username;
		$this->_client = new Zend_Soap_Client(self::WSDL, $this->_clientOptions);
	}

	/**
 	 * Converts a timestamp to a date format AT accepts.
 	 * @param Int $timestamp Unix timestamp
 	 * @return String SOAP timestamp
 	 */
	public function convertTimestampUnixToSoap($timestamp = null) {
		if (is_null($timestamp)) {
			return strftime(self::DATETIME_FORMAT);
		}
		/**
 		 * @todo: timezone erachter?
 		 */
		return strftime(self::DATETIME_FORMAT, $timestamp);
	}

	/**
 	 * Converts a timestamp to a date format AT accepts.
 	 * @param String $timestamp SOAP timestamp
 	 * @return Int Unix timestamp
 	 */
	public function convertTimestampSoapToUnix($soap = null) {
		$dt = new DateTime($soap);
		return $dt->format('U');
	}

	/**
 	 * @param String $method Name of the SOAP function
 	 * @param Array $args Arguments to provide the SOAP function
 	 * @return Garp_Service_ActiveTickets_Response
 	 */
	public function __call($method, $args) {
		$args = current($args);
		$args = $this->_addUsername($args);	
		
		$response = $this->_client->$method($args);
		$responseObj = new Garp_Service_ActiveTickets_Response($response, $method);
		
		return $responseObj;
	}

	protected function _addUsername(array $args) {
		$args['Clientname'] = $this->_username;
		return $args;
	}
}
