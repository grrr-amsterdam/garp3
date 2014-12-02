<?php
/**
 * Garp_Service_CinemaNl
 * API wrapper for the Cinema.nl movie database.
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage CinemaNl
 * @lastmodified $Date: $
 */
class Garp_Service_CinemaNl extends Zend_Service_Abstract {

    protected $_basePath = "http://www.cinema.nl/api/1/rest";



	public function __construct() {
		// $this->_rest = new Zend_Rest_Client('http://www.cinema.nl/api/1/rest');
		
	}


	/**
	 * @param String $since The last changed / added date to filter the results by, in the 2012-12-31 format.
	 * 						Since it's not aware of time (only date), it's possible that changes are made after the last sync,
	 * 						on the same day. That's why we subtract another day from the provided filter day.
	 * @return Array A numeric array of cinema.nl movie IDs.
	 */
	public function fetchMovieIds($since = null) {
		if ($since) {
			$since = date('Y-m-d', strtotime('-1 day', strtotime($since)));
		}
		$response = $this->_getRequest("/movie".($since ? "/since/{$since}" : ''));

		if (
			property_exists($response, 'ids') &&
			property_exists($response->ids, 'id')
		) {
			return $response->ids->id;
		} else throw new Zend_Service_Exception("Received unexpected response from Cinema.nl: missing id list.");
	}
	

	/**
	 * @param Int $id The cinema.nl movie id, for instance 269865.
	 * @param Boolean $raw When you want the unaltered response from Cinema.nl (As SimpleXML element)
	 * @return Array Associative array with movie data:
	 * 					String name
	 * 					String synopsis
	 * 					Int year
	 * 					Int length
	 * 					String directors
	 * 					String producers
	 * 					String scenarists
	 * 					String actors
	 * 					String countries
	 * 					Boolean color
	 */
	public function fetchMovie($id, $raw = false) {
		try {
			$response = $this->_getRequest("/movie/".$id);
		} catch(Exception $e) {
			if (strpos($e->getMessage(), '406') !== false) {
				throw new Exception("The movie ID you provided ({$id}) was not accepted by Cinema.nl. It's likely that this movie doesn't exist.", 406);
			} elseif (strpos($e->getMessage(), '500') !== false) {
				throw new Exception("There was a 500 server error fetching details for movie {$id}.", 500);
			} else throw $e;
		}

		if ($raw) {
			return $response;
		}
		$output = array();

		if (
			property_exists($response, 'title')
		) {
			$title = (string)$response->title;
			
			if (!empty($title)) {
				$output['name'] = $title;

				$year = $this->_extractNumericProp($response, 'year', $id);
				$output['year'] = ($year > 0 && $year) ? $year : null;
				
				$length = $this->_extractNumericProp($response, 'playTime', $id);
				$output['length'] = ($length > 0 && $length) ? $length : null;

				$output['directors'] = $this->_extractPeopleProp($response, 'relatedDirectors', 'relatedDirector') ?: null;
				$output['producers'] = $this->_extractPeopleProp($response, 'relatedProducers', 'relatedProducer') ?: null;
				$output['scenarists'] = $this->_extractPeopleProp($response, 'relatedScenarists', 'relatedScenarist') ?: null;
				$output['actors'] = $this->_extractPeopleProp($response, 'relatedActors', 'relatedActor') ?: null;

				$output['synopsis'] = $this->_extractSynopsis($response) ?: null;
				$output['countries'] = $this->_extractCountries($response) ?: null;
				$output['color'] = $this->_extractColor($response) ?: 1;

				$output['genres'] = $this->_extractGenres($response) ?: null;

				return $output;
			} else {
				throw new Zend_Service_Exception("Received empty title field for movie {$id}.");
			}
		} else throw new Zend_Service_Exception("Received response without title field for movie {$id}.");
	}


	protected function _extractGenres(SimpleXmlElement $response) {
		if ($genres = $this->_extractStringArrayProp($response, 'genres', 'genre')) {
			return (array)$genres;
		}
	}
	
	
	protected function _extractStringArrayProp(SimpleXmlElement $response, $propName, $propChildName) {
		if (
			property_exists($response, $propName) &&
			$response->{$propName}
		) {
			if (property_exists($response->{$propName}, $propChildName)) {
				$output = array();
				foreach ($response->{$propName}->{$propChildName} as $node) {
					$output[] = (string)$node;
				}
				return $output;	
			} else {
				return (array)$response->{$propName};
			}
		}		
	}


	protected function _extractCountries(SimpleXmlElement $response) {
		if ($countries = $this->_extractStringArrayProp($response, 'countries', 'country')) {
			return Garp_Util_String::humanList($countries, null, 'en');	
		}
	}


	protected function _extractColor(SimpleXmlElement $response) {
		if (
			property_exists($response, 'color') &&
			!empty($response->color)
		) {
			return $response->color !== 'zw';
		}
	}
	
	
	protected function _extractNumericProp(SimpleXmlElement $response, $propName, $movieId) {
		if (
			property_exists($response, $propName) &&
			!empty($response->{$propName})
		) {
			$value = (string)$response->{$propName};
			if (is_numeric($value)) {
				return (int)$value;
			} else throw new Zend_Service_Exception("Received non-numeric {$propName} property for movie {$movieId}.");
		}
	}
	
	
	protected function _extractPeopleProp(SimpleXmlElement $response, $propName, $propChildName) {
		if (
			property_exists($response, $propName) &&
			property_exists($response->{$propName}, $propChildName) &&
			count($response->{$propName}->{$propChildName})
		) {
			$people = array();
			foreach ($response->{$propName}->{$propChildName} as $person) {
				if (
					property_exists($person, 'name') &&
					!empty($person->name)
				) {
					$people[] = $person->name;
				}
			}
		
			if ($people) {
				return Garp_Util_String::humanList($people, null, 'en');
			}
		}
	}
	
	
	protected function _extractSynopsis(SimpleXMLElement $response) {
		/**
 	 	 * 25 March 2013: disabled currentDescription field as per this ticket:
 	 	 * @see http://projects.grrr.nl/projects/we-want-cinema-website/tasks/1486
 	 	 */
		$fields = array('description', 'shortPlainDescription', /*'currentDescription',*/ 'mediumPlainDescription');
		/** This notice string is sometimes used by cinema.nl to indicate a description field is not yet available. */
		$descriptionEmptyString = "Voor deze film is helaas nog geen beschrijving beschikbaar";

		$extractSynopsisField = function(SimpleXMLElement $response, $fieldName, $descriptionEmptyString) {
			if (
				property_exists($response, $fieldName) &&
				!empty($response->{$fieldName}) &&
				stripos($response->{$fieldName}, $descriptionEmptyString) === false
			) {
				return $response->{$fieldName};
			}
		};

		foreach ($fields as $fieldName) {
			if ($synopsis = $extractSynopsisField($response, $fieldName, $descriptionEmptyString)) {
				return $synopsis;
			}
		}
	}


	/**
	 * @param String $path The path of the API command, minus domain name and base path. For instance: /movie/12345
	 * @return DOMDocument The response body, in case of a valid response.
	 */
	protected function _getRequest($path) {
		$uri  = $this->_basePath.$path.".xml";
		$response = $this->getHttpClient()
						 ->setMethod(Zend_Http_Client::GET)
						 ->setUri($uri)
						 ->request()
		;

		if (!$response->isError()) {
			$xmlDoc = new SimpleXMLElement($response->getBody());
			return $xmlDoc;
		} else {
			$statusCode = $response->getStatus();
			throw new Zend_Service_Exception("There was a {$statusCode} error contacting Cinema.nl.");
		}
	}
}
