<?php
/**
 * G_View_Helper_FileSize
 * Gives a formatted string describing the size of a file
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class G_View_Helper_FileSize extends Zend_View_Helper_Abstract {
	/**
	 * Size constants
	 * @var Array
	 */
	protected $_sizes = array('bytes', 'Kb', 'Mb', 'Gb', 'Tb', 'Pb', 'Eb', 'Zb', 'Yb');
	
	
	/**
	 * Format a filesize
	 * @param Int $size In bytes
	 * @return String
	 */
	public function fileSize($size) {
		if (!$size) {
			return '0 '.$this->_sizes[0];
		} else {
			return (round($size/pow(1024, ($i = floor(log($size, 1024)))), $i > 1 ? 2 : 0).' '.$this->_sizes[$i]);
		}
	}
}