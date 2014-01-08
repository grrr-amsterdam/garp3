<?php
/**
 * Garp_Service_Google_Maps_Response
 * 
 * Google Maps response
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Google
 * @lastmodified $Date: $
 */
class Garp_Service_Google_Maps_Response {
	public $city;
	public $province;
	public $municipality;
	public $latitude;
	public $longitude;
	public $error = false;


	/**
 	 * @param String $rawResponse Raw API response
 	 */
	public function __construct($rawResponse) {
		$response = json_decode($rawResponse, true);

		if (!$response || !$this->_isValidResponse($response)) {
			$this->error = true;
			return;
		}

		$this->_loadResponse($response);
	}

	/**
 	 * Validates Google API response.
 	 * @param Array $response
 	 * @return Void
 	 */
	protected function _isValidResponse(array $response) {
		return (
			array_key_exists('results', $response) &&
			array_key_exists(0, $response['results']) &&
			array_key_exists('address_components', $response['results'][0]) &&
			array_key_exists('geometry', $response['results'][0])
		);
	}

	/**
 	 * @param String $response
 	 */
	protected function _loadResponse($response) {
		$this->city 		= $this->_getCity($response);
		$this->province 	= $this->_getProvince($response);
		$this->municipality = $this->_getMunicipality($response);
		$this->latitude 	= $this->_getLatitude($response);
		$this->longitude 	= $this->_getLongitude($response);
	}

	protected function _getCity($response) {
		return $this->_getAddressComponent($response, 'locality');
	}
	
	protected function _getProvince($response) {
		return $this->_getAddressComponent($response, 'administrative_area_level_1');
	}

	protected function _getMunicipality($response) {
		return $this->_getAddressComponent($response, 'administrative_area_level_2');
	}

	protected function _getLocation($response) {
		return $response['results'][0]['geometry']['location'];
	}

	protected function _getLatitude($response) {
		$location = $this->_getLocation($response);
		return $location['lat'];
	}

	protected function _getLongitude($response) {
		$location = $this->_getLocation($response);
		return $location['lng'];
	}

	protected function _getAddressComponent($response, $type) {
		$addressComponents = $response['results'][0]['address_components'];

		foreach ($addressComponents as $componentIndex => $c) {
			$addressTypes = $c['types'];
			$foundKey = array_search($type, $addressTypes);

			if ($foundKey !== false) {
				return $addressComponents[$componentIndex]['long_name'];
			}
		}
	}
}
