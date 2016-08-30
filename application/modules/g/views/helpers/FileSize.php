<?php
/**
 * G_View_Helper_FileSize
 * Gives a formatted string describing the size of a file
 *
 * @package G_View_Helper
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class G_View_Helper_FileSize extends Zend_View_Helper_Abstract {
    /**
     * Size constants
     *
     * @var array
     */
    protected $_sizes = array('bytes', 'Kb', 'Mb', 'Gb', 'Tb', 'Pb', 'Eb', 'Zb', 'Yb');

    /**
     * Format a filesize
     *
     * @param int $size In bytes
     * @return string
     */
    public function fileSize($size) {
        if (!$size) {
            return '0 ' . $this->_sizes[0];
        }
        return round($size/pow(1024, ($i = floor(log($size, 1024)))), $i > 1 ? 2 : 0) .
            ' ' . $this->_sizes[$i];
    }
}
