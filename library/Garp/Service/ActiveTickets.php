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
 	 * @param String $method Name of the SOAP function
 	 * @param Array $args Arguments to provide the SOAP function
 	 */
	public function __call($method, $args) {
		$args = current($args);
		$args = $this->_addUsername($args);	
		
		return $this->_client->$method($args);

		$resultKey = $methodName . 'Result';
		$xml = $response->$resultKey;
		//echo $response;
		//echo $xml;

		$xmlParser = new Zend_Config_Xml($xml);

		//// levert op: array('ProgramList'=> array('Program' => array(0 => array(prop1, prop2, etc))))
		var_dump($xmlParser->toArray());
		//return parent::$method($args);
	}

	protected function _addUsername(array $args) {
		$args['Clientname'] = $this->_username;
		return $args;
	}
}
