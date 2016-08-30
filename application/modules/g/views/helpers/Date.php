<?php
/**
 * G_View_Helper_Date
 *
 * @package G_View_Helper
 * @author  David Spreekmeester <david@grrr.nl>
 */
class G_View_Helper_Date extends Zend_View_Helper_BaseUrl {
    public function date() {
        return $this;
    }

    /**
     * Formats dates according to configuration settings in the ini file.
     *
     * @param string $type Name of the format, as defined in the ini file.
     *                     The ini value can be in either format.
     * @param string $date MySQL datetime string
     * @return string
     */
    public function format($type, $date) {
        return Garp_DateTime::formatFromConfig($type, $date);
    }

    /**
     * Render human-readable time, displayed as f.i. "2:50".
     *
     * @param int $minutes Minutes as an integer
     * @return string
     */
    public function displayMinutesAsTime($minutes) {
        $hours = floor($minutes / 60);
        $leftOverMinutes = str_pad($minutes % 60, 2, 0, STR_PAD_LEFT);
        return $hours . ':' . $leftOverMinutes;
    }
}
