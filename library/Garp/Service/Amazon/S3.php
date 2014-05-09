<?php
/**
 * This class extends Zend's S3 functionality, fixing the 1000 items limit
 * that the S3 service sets by default, when fetching an object list.
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Service_Amazon_S3 extends Zend_Service_Amazon_S3 {

	/**
	 * @author David Spreekmeester | grrr.nl
	 * @original Edgar Hassler, http://framework.zend.com/issues/browse/ZF-7675
	 */
    public function getObjectsByBucket($bucket, $params = array()) {
        $objects = array();
		$leadMarker = '';

		do {
			$params['marker'] = $leadMarker;
	        $response = $this->_makeRequest('GET', $bucket, $params);

	        if ($response->getStatus() != 200) {
	            return false;
	        }

	        $xml = new SimpleXMLElement($response->getBody());


	        if (isset($xml->Contents)) {
	            foreach ($xml->Contents as $contents) {
	                foreach ($contents->Key as $object) {
	                    $objects[] = (string)$object;
	                }
	            }
	        }
	        // The lead marker is the last key
	        $leadMarker = $objects[count($objects) - 1];
	    } while( /* Until we exhaust the elements. */ $xml->IsTruncated == 'true');

        return $objects;
    }
}