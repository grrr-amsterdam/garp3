<?php
/**
 * Garp_Service_Gofilex
 * Service to talk to the Gofilex movie database.
 * @author Harmen 'Greetje' Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Gofilex
 * @lastmodified $Date: $
 */
class Garp_Service_Gofilex extends Zend_Service_Abstract {
	/**
 	 * Soap client
 	 * @var Zend_Soap_Client
 	 */
	protected $_client;


	/**
 	 * Class constructor
 	 * @param String $wdsl URL to the service's WDSL
 	 * @return Void
 	 */
	public function __construct($wdsl = null) {
		if (!$wdsl) {
			$ini = Zend_Registry::get('config');
			if (empty($ini->gofilex->wdsl)) {
				throw new Garp_Service_Gofilex_Exception('No Gofilex WDSL given or defined in application.ini.');
			}
			$wdsl = $ini->gofilex->wdsl;
		}
		$this->_client = new Zend_Soap_Client($wdsl, array(
			'compression'  => SOAP_COMPRESSION_ACCEPT,
			'soap_version' => SOAP_1_1
		));
	}


	/**
 	 * Get all movies
 	 * @return Array
 	 */
	public function getMovies() {
		try {
			$response = $this->_client->GETREPORT('film');
			$this->_logTraffic();
		} catch (Exception $e) {
			$this->_throwException($e, 'GETREPORT(film)');
		}
		return $response->GENERALINFOARRAY;
	}


	/**
 	 * Get all theaters
 	 * @return Array
 	 */
	public function getTheaters() {
		try {
			$response = $this->_client->GETREPORT('bioscoop');
			$this->_logTraffic();
		} catch (Exception $e) {
			$this->_throwException($e, 'GETREPORT(bioscoop)');
		}
		return $response->GENERALINFOARRAY;
	}


	/**
 	 * Fetch unavailability of a given movie
 	 * @param Array $args
 	 * @return Array
 	 */
	public function getMovieUnavailability(array $args) {
		$args = $args instanceof Garp_Util_Configuration ? $args : new Garp_Util_Configuration($args);
		$args->obligate('movieId')
			->obligate('distributorId')
			->setDefault('fromDate', date('Ymd'))
			->setDefault('orderId', uniqid())
			;
		try {
			$response = $this->_client->GETPRINTSTATUS(
				$args['orderId'],
				$args['movieId'],
				$args['distributorId'],
				$args['fromDate']
			);
			$this->_logTraffic();
		} catch (Exception $e) {
			$this->_throwException($e, 'GETPRINTSTATUS', $args);
		}

		$out = array();
		foreach ($response->PRINTSTATUSARRAY as $medium) {
			if (!is_object($medium)) {
				continue;
			}
			if (!array_key_exists($medium->TYPEDRAGER, $out)) {
				$out[$medium->TYPEDRAGER] = array();
			}
			// Fools leave whitespace instead of NULL
			$medium->GEBLOKKEERDEPERIODE = trim($medium->GEBLOKKEERDEPERIODE);
			if ($medium->GEBLOKKEERDEPERIODE) {
				$dates = explode('-', $medium->GEBLOKKEERDEPERIODE);
				$out[$medium->TYPEDRAGER][] = array(
					strtotime($dates[0]),
					strtotime($dates[1])
				);
			}
		}
		return $out;
	}


	/**
 	 * Make a reservation
 	 * @param Array $args
 	 * @return Boolean
 	 */
	public function makeReservation(array $args) {
		return $this->_printBooking('reservering', $args);
	}


	/**
 	 * Cancel a reservation
 	 * @param Array $args
 	 * @return Boolean
 	 */
	public function cancelReservation(array $args) {
		return $this->_printBooking('annreservering', $args);
	}


	/**
 	 * Make a booking
 	 * @param Array $args
 	 * @return Boolean
 	 */
	public function makeBooking(array $args) {
		return $this->_printBooking('boeking', $args);
	}


	/**
 	 * Cancel a booking
 	 * @param Array $args
 	 * @return Boolean
 	 */
	public function cancelBooking(array $args) {
		return $this->_printBooking('annboeking', $args);
	}


	/**
 	 * Make the PRINTBOOKING call, used by $this->makeReservation, $this->cancelReservation, $this->makeBooking and $this->cancelBooking.
 	 * @param String $method
 	 * @param Array $args
	 * @return Boolean
	 */
	protected function _printBooking($method, array $args) {
		// Allow fake bookings on Staging servers as to not pollute the Gofilex database
		$ini = Zend_Registry::get('config');
		if (!$ini->gofilex->realBookings) {
			$response = new stdClass();
			$response->SUCCESS = 1;
			return $response;
		}
		
		$args = $args instanceof Garp_Util_Configuration ? $args : new Garp_Util_Configuration($args);
		$args->obligate('movieId')
			->obligate('distributorId')
			->obligate('theaterId')
			->obligate('medium')
			->obligate('date')
			->setDefault('orderId', uniqid())
			;
		try {
			$response = $this->_client->PRINTBOOKING(
				$method,
				$args['orderId'],
				$args['movieId'],
				$args['distributorId'],
				$args['theaterId'],
				$args['medium'],
				$args['date']
			);
			$this->_logTraffic();
		} catch (Exception $e) {
			$this->_throwException($e, 'PRINTBOOKING('.$method.')', $args);
		}
		$response = $response->GETPRINTBOOKINGARRAY[0];

		if (!$response->SUCCESS) {
			throw new Garp_Service_Gofilex_Exception($response->FOUTMELDING);
		}
		return $response;
	}


	/**
 	 * Throw exception and do some logging.
 	 * @param Exception $e
 	 * @param String $method
 	 * @param Array $args
 	 * @return Void
 	 */
	protected function _throwException(Exception $e, $method, $args = array()) {
		$this->_logTraffic();
		
		// Mail Amstelfilm about this error
		$ini = Zend_Registry::get('config');
		if (!empty($ini->gofilex->errorReportEmailAddress)) {
			$to = $ini->gofilex->errorReportEmailAddress;
			$subject = 'Gofilex error report';
			$message = "Hallo,\n\r\n\r".
				"Er is een Gofilex fout opgetreden. Hier vindt u de details:\n\r\n\r".
				"Fout: ".$e->getMessage()."\n\r".
				"Gofilex API functie: $method\n\r".
				"Parameters:\n";
			foreach ($args as $key => $value) {
				$message .= "$key: $value\n\r";
			}

			// Especially interesting is the submitted POST data, since here 
			// user-submitted movie data will reside
			$message .= "\n\rPOST data (ingevuld in het formulier):\n\r";
			if (empty($_POST)) {
				$message .= "-\n\r";
			} else {
				foreach ($_POST as $key => $value) {
					$message .= "$key: $value\n\r";
				}
			}
			
			@mail($to, $subject, $message);
		}

		throw $e;
	}


	/**
	 * Keep track of all requests and their responses in a log file
	 * @return Void
 	 */
	protected function _logTraffic() {
		if ('testing' !== APPLICATION_ENV) {
			$lastRequest = $this->_client->getLastRequest();
			$lastResponse = $this->_client->getLastResponse();
			$filename = date('Y-m-d').'-gofilex.log';
			$logMessage  = "\n";
			$logMessage .= '[REQUEST]'."\n";
			$logMessage .= $lastRequest."\n\n";
			$logMessage .= '[RESPONSE]'."\n";
			$logMessage .= $lastResponse."\n\n";

			dump($filename, $logMessage);
		}
	}
}
