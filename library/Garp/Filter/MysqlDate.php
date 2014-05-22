<?php
/**
 * Garp_Filter_MysqlDate
 * Converts to a MySQL compatible date
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      Garp_Filter
 */
class Garp_Filter_MysqlDate implements Zend_Filter_Interface {
	
	public function filter($value) {
		if (!$value) {
			return '';
		}
		list($day, $month, $year) = sscanf($value, '%d-%d-%d');
		$date = "{$year}-{$month}-{$day}";
		return $date;
	}

}
