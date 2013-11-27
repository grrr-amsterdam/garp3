<?php
/**
 * G_Model_Location
 * Standard implementation of a (Dutch) location, based on the 6PP postal code database.
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @lastmodified $Date: $
 */
class G_Model_Location extends Model_Base_Location {

	/**
 	 * Fetches the location record from the database if it exists.
 	 * If not, a call is made to the Google Maps API,
 	 * and the result is stored in the database.
 	 */
	public function fetchRowByZip($zip) {
		$zip = $this->_normalizeZip($zip);
		$row = $this->_fetchRowByZipFromDatabase($zip);

		if ($row) {
			return $row;
		}

		$googleLocation = $this->_fetchRowByZipFromGoogle($zip);

		if (!$googleLocation || $googleLocation->error) {
			return null;
		} 

		$this->_storeLocation($zip, $googleLocation);
		$row = $this->_fetchRowByZipFromDatabase($zip);

		return $row;
	}

	/**
 	 * Normalize the input so that it matches the stored format.
 	 */
	protected function _normalizeZip($zip) {
		if (strlen($zip) === 6) {
			$zip = substr($zip, 0, 4) . ' ' . substr($zip, 4, 2);
		}

		return $zip;
	}

	protected function _fetchRowByZipFromDatabase($zip) {
		$select = $this->select()->where('zip = ?', $zip);
		$row = $this->fetchRow($select);

		return $row;
	}

	/**
 	 * @param String $zip
 	 * @return Garp_Service_Google_Maps_Response
 	 */
	protected function _fetchRowByZipFromGoogle($zip) {
		$api = new Garp_Service_Google_Maps();
		$response = $api->fetchLocation($zip);

		return $response;
	}

	protected function _storeLocation($zip, Garp_Service_Google_Maps_Response $location) {
		$row = (array)$location;
		unset($row['error']);
		$row['zip'] = $zip;
		$this->insert($row);
	}
}
