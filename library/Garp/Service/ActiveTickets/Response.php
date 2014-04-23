<?php
/**
 * Garp_Service_ActiveTickets_Response
 * @author David Spreekmeester | Grrr.nl
 */
class Garp_Service_ActiveTickets_Response extends ArrayObject {
	/**
 	 * @param String $rawXmlResponse The response that comes back directly from the AT SOAP service.
 	 * @param String $methodName Name of the SOAP method called.
 	 */
	public function __construct($rawXmlResponse, $methodName) {
		$resultKey = $methodName . 'Result';
		$xml = $rawXmlResponse->$resultKey;

		$xmlParser = new Zend_Config_Xml($xml);
		$resultArray = $xmlParser->toArray();

		$this->exchangeArray($resultArray);
	}
}
