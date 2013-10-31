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
		if (is_array($column)) {
			// Convert so it can be used by array_intersect_key
			$column = array_fill_keys($column, null);
		}
		foreach ($this as $row) {
			$flat = is_array($column) ? array_intersect_key($row->toArray(), $column) : $row->{$column};
			$out[] = $flat;
		}
		return $out;
	}

}
