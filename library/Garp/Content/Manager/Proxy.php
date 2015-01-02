<?php
/**
 * Garp_Content_Manager_Proxy
 * This object passes method calls along to Garp_Content_Manager.
 * It is primarily used to provide an acceptable interface via JSON-RPC.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.5
 * @package      Garp_Content_Manager
 */
class Garp_Content_Manager_Proxy {
	/**
	 * Pass methods along to Garp_Content_Manager.
	 * @param String $model The desired model to manipulate
	 * @param String $method The desired method to execute
	 * @param Array  $args   Arguments
	 * @return Mixed Whatever the Garp_Content_Manager returns, optionally converted to array.
	 */
	public function pass($model, $method, $args = array()) {
		$manager = new Garp_Content_Manager(
			Garp_Content_Api::modelAliasToClass($model));

		if (!method_exists($manager, $method)) {
			throw new Garp_Content_Exception('Unknown method requested.');
		}
		$params = !empty($args) ? $args[0] : array();
		$result = $this->_produceResult($manager, $method, $params);

		if ($result instanceof Zend_Db_Table_Rowset_Abstract ||
			$result instanceof Zend_Db_Table_Row_Abstract) {
			$result = $result->toArray();
		}
		return $result;
	}

	protected function _produceResult(Garp_Content_Manager $manager, $method, $params) {
		try {
			$result = call_user_func_array(array($manager, $method), array($params));
		} catch (Zend_Db_Statement_Exception $e) {
			$this->_handleDatabaseException($e);
		}
		return $result;
	}

	/**
 	 * Look for 'Duplicate entry' exceptions, and convert to a human-friendly message.
 	 */
	protected function _handleDatabaseException(Zend_Db_Statement_Exception $e) {
		if (strpos($e->getMessage(), 'Duplicate entry') === false) {
			throw $e;
		}
		// Note the double spaces in the template string are required since quotes would be
		// added greedily to the parsed values.
		list($value, $index) = sscanf($e->getMessage(),
			'SQLSTATE[23000]: Integrity constraint violation: ' .
			'1062 Duplicate entry  %s  for key  %s ');

		// Throw an exception with a human-friendly error
		throw new Exception(
			sprintf(__('%s is already in use, please provide a unique value.'), $value));
	}
}
