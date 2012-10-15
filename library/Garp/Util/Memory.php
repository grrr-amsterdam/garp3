<?php
/**
 * Garp_Util_Memory
 * Shortcut to toggling memory.
 *
 * @author       $Author: harmen $
 * @modifiedby   $LastChangedBy: harmen $
 * @version      $LastChangedRevision: 6262 $
 * @package      Garp
 * @subpackage   Util
 * @lastmodified $LastChangedDate: 2012-09-12 15:17:49 +0200 (Wed, 12 Sep 2012) $
 */
class Garp_Util_Memory {
	/**
 	 * Raises PHP's RAM limit for extensive operations.
 	 * Takes its value from application.ini if not provided.
 	 * @param Int $mem In MBs.
 	 * @return Void
 	 */
	public function useHighMemory($mem = null) {
		$ini = Garp_Cache_Ini::factory(APPLICATION_PATH.'/configs/application.ini');
		if (empty($ini->app->highMemory)) {
			return;
		}
		$highMemory = $ini->app->highMemory;
		$currentMemoryLimit = $this->getCurrentMemoryLimit();
		if (!empty($currentMemoryLimit)) {
			$megs = (int)substr($currentMemoryLimit, 0, -1);
			if ($megs < $highMemory) {
				ini_set('memory_limit', $highMemory.'M');
			}
		} else {
			ini_set('memory_limit', $highMemory.'M');
		}
	}


	/**
 	 * Return current ini setting memory_limit
 	 * @return Int
 	 */
	public function getCurrentMemoryLimit() {
		return ini_get('memory_limit');
	}
}
