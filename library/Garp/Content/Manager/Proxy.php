<?php
/**
 * Garp_Content_Manager_Proxy
 * This object passes method calls along to Garp_Content_Manager.
 * It is primarily used to provide an acceptable interface via JSON-RPC.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Manager
 * @lastmodified $Date: $
 */
class Garp_Content_Manager_Proxy {	
	/**
	 * Pass methods along to Garp_Content_Manager.
	 * @param String $model The desired model to manipulate
	 * @param String $method The desired method to execute
	 * @param Array  $args   Arguments
	 * @return Mixed Whatever the Content_Manager returns, in the case of Garp_model objects converted to array.
	 */
	public function pass($model, $method, $args = array()) {
		$modelClass = Garp_Content_Api::modelAliasToClass($model);
		$manager = new Garp_Content_Manager($modelClass);
		if (method_exists($manager, $method)) {
			$params = !empty($args) ? $args[0] : array();
			$result = call_user_func_array(array($manager, $method), array($params));
		} else {
			throw new Garp_Content_Exception('Unknown method requested.');
		}
		if ($result instanceof Zend_Db_Table_Rowset_Abstract || $result instanceof Zend_Db_Table_Row_Abstract) {
			$result = $result->toArray();
		}
		return $result;
	}
}