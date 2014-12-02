<?php
/**
 * Garp_Db_Table_Rowset
 * Custom implementation of Zend_Db_Table_Rowset.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Garp_Db_Table
 */
 
class Garp_Db_Table_Rowset extends Zend_Db_Table_Rowset_Abstract {

	/**
 	 * Flatten a rowset to a simpler array containing the specified columns.
 	 * @param Mixed $columns Array of columns or column.
 	 * @return Array
 	 */
	public function flatten($column) {
		$out = array();
		foreach ($this as $row) {
			$out[] = $row->flatten($column);
		}
		return $out;
	}

}
