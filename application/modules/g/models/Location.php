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
 	 * Create new Location record, or update existing one
 	 * (lookup based on zipcode)
 	 * @param String $zipcode
 	 * @param Int $number
 	 * @return Int Primary key
 	 */
	public function insertOrUpdate($zipcode, $number) {
		$zipcode = $this->_normalizeZip($zipcode);
		$select = $this->select()
			->where('zip = ?', $zipcode);
		$row = $this->fetchRow($select);
		/**
 		 * Create new records for unknown zips
 		 * or when the zip is known, but row is
 		 * used for a different number
 		 */
		if (!$row || $row->number != $number) {
			$row = $this->createRow();
		}
		if (!$row->isConnected()) {
			$row->setTable($this);
		}
		$row->zip = $zipcode;
		$row->number = $number;
		$row->save();
		return $row->id;
	}

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
			$zip = substr($zip, 0, 4) . ' ' . strtoupper(substr($zip, 4, 2));
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
		$row['source'] = 'Google';
		$this->insert($row);
	}
}
