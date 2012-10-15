<?php
/**
 * Garp_Service_Google_Chart
 * 
 * Google Chart implementations
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Google
 * @lastmodified $Date: $
 */
class Garp_Service_Google_Chart {
	/**
	 * Google Chart API base url
	 * @var String
	 */
	const CHART_API_URL = 'http://chart.apis.google.com/chart?';
	
	

	/**
	 * Fetches the URL to the QR code image for a specific reference URL.
	 * 
	 * @param String $refUrl The unencoded URL this QR code should point to. A QR code identifier is automatically attached at the end
	 * @return String URL to the QR code image
	 */
	public function fetchQRCodeUrl($refUrl) {
		$size = 150;
		$EC_level = 'L';
		$margin = 0;
		$trail = '?qr=1';

		return self::CHART_API_URL
				.'chs='.$size.'x'.$size.'&cht=qr'
				.'&chld='.$EC_level.'|'.$margin
				.'&chl='
				.urlencode($refUrl.$trail);
	}
}