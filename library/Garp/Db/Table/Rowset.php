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

	/**
 	 * Project a rowset using $fn
 	 * @param Function $fn
 	 * @return Garp_Db_Table_Rowset
 	 */
	public function map($fn) {
		$rows = array_map($fn, $this->toArray());
		$out = new $this(array(
            'table'    => $this->getTable(),
            'data'     => $rows,
            'readOnly' => $this->_readOnly,
            'rowClass' => $this->_rowClass,
            'stored'   => true
        ));
		return $out;
	}
}
