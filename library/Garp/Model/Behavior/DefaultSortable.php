<?php
/**
 * Garp_Model_Behavior_DefaultSortable
 * Makes sure a model is sorted by the same column by default for 
 * every query, so developers are allowed to forget to add this
 * ORDER clause to their queries.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class Garp_Model_Behavior_DefaultSortable extends Garp_Model_Behavior_Core {
	/**
	 * Wether to execute before or after regular observers.
	 * @var String
	 */
	protected $_executionPosition = self::EXECUTE_LAST;
	
	
	/**
	 * Configuration.
	 * @return Void
	 */
	protected function _setup($config) {}
	
	
	/**
	 * Before fetch callback.
	 * Make sure a default order is set on queries.
	 * This can be set thru $model::_defaultOrder. If not given, 
	 * $model->_primary will be used, but only when it is a single column.
	 * Compound keys are ignored in this case.
	 * All this is only applicable when no ORDER clause is available in the Select object.
	 * @param Array $args
	 * @return Void
	 */
	public function beforeFetch(&$args) {
		$model = &$args[0];
		$select = &$args[1];
		
		if (!$select->getPart(Zend_Db_Select::ORDER)) {
			if (is_null($model->getDefaultOrder())) {
				$primary = $model->info(Zend_Db_Table_Abstract::PRIMARY);
				if (is_array($primary)) {
					return;
				}
				$order = is_array($primary) ? $primary : $primary.' DESC';
			} else {
				$order = $model->getDefaultOrder();
			}
			$select->order($order);
		}
	}
}
