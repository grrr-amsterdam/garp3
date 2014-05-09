<?php
/**
 * Garp_DateTime
 * Extends native DateTime. Used to provide support for
 * localized dates. Date-related helper methods can be
 * added in the future.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Garp_DateTime
 */
class Garp_DateTime extends DateTime {

	/**
 	 * Support for localized formatting.
 	 * @param String $format
 	 * @return String
 	 */
	public function format_local($format) {
		// Configure the timezone to account for timezone and/or daylight savings time
		$timezone = new DateTimeZone(date_default_timezone_get());
		$this->setTimezone($timezone);
		
		$timestamp = $this->getTimestamp();
		return strftime($format, $timestamp);
	}

}
