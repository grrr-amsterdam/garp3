<?php
/**
 * Garp_Service_Google_Maps
 * 
 * Google Maps implementation
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Google
 * @lastmodified $Date: $
 */
class Garp_Service_Google_Maps {
	/**
	 * Google API base url
	 * @var String
	 */
	const API_URL = 'http://maps.googleapis.com/maps/api/geocode/json?';
	
	

	/**
	 * Fetches location data for given address component
	 * 
	 * @param String $address The address string, can be anything: zip, street, etc.
	 * @return Garp_Service_Google_Maps_Response The elaborate location data from Google.
	 */
	public function fetchLocation($address, $country = null) {
		$params = array(
			'address' => urlencode($address),
			'sensor' => 'false'
		);

		if ($country) {
			$params['components'] = 'country:' . $country;
		}

		$url = self::API_URL . http_build_query($params);
		$rawResponse = file_get_contents($url);
		$response = new Garp_Service_Google_Maps_Response($rawResponse);

		return $response;
	}

}
