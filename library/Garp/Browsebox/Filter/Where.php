<?php
/**
 * Garp_Browsebox_Filter_Where
 * Modify the WHERE clause with conditions determined at runtime.
 *
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Browsebox
 * @lastmodified $Date: $
 */
class Garp_Browsebox_Filter_Where extends Garp_Browsebox_Filter_Abstract {
	/**
	 * Conditions, should all be strings fit for use with Zend_Db_Select::where(),
	 * e.g. "foo = ?" and "bar LIKE ?".
	 * @var Array
	 */
	protected $_config = array();


	/**
	 * Values that will be used in conjunction with what's in $this->_config.
	 * @var Boolean
	 */
	protected $_values;


	/**
	 * Setup the filter
	 * @param Array $params
	 * @return Void
	 */
	public function init(array $params = array()) {
		if (count($params) !== count($this->_config)) {
			throw new Garp_Browsebox_Exception('Number of parameters does not match the number of required parameters in filter "'.$this->getId().'"');
		}
		$this->_values = $params;
	}


	/**
	 * Modify the Select object
	 * @param Zend_Db_Select $select
	 * @return Void
	 */
	public function modifySelect(Zend_Db_Select &$select) {
		foreach ($this->_config as $i => $condition) {
			$select->where($condition, $this->_values[$i]);
		}
	}
}
