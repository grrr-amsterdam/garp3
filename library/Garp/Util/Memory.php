<?php
/**
 * Garp_Util_Memory
 * Shortcut to toggling memory.
 *
 * @package Garp_Util
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Util_Memory {
    /**
     * Raises PHP's RAM limit for extensive operations.
     * Takes its value from application.ini if not provided.
     *
     * @param int $mem In MBs.
     * @return Void
     */
    public function useHighMemory($mem = null) {
        $ini = Zend_Registry::get('config');
        if (empty($ini->app->highMemory)) {
            return;
        }
        $highMemory = $ini->app->highMemory;
        $currentMemoryLimit = $this->getCurrentMemoryLimit();
        if (!empty($currentMemoryLimit)) {
            $megs = (int)substr($currentMemoryLimit, 0, -1);
            if ($megs < $highMemory) {
                ini_set('memory_limit', $highMemory . 'M');
            }
        } else {
            ini_set('memory_limit', $highMemory . 'M');
        }
    }


    /**
     * Return current ini setting memory_limit
     *
     * @return int
     */
    public function getCurrentMemoryLimit() {
        return ini_get('memory_limit');
    }
}
