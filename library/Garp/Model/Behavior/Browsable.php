<?php
/**
 * Garp_Model_Behavior_Browsable
 * Lets models have a certain URL mapping. This must always be a sprintf() compatible
 * string, so that variables may be substituted easily.
 * This behavior tries to figure out the URL itself from APPLICATION_PATH/configs/routes.ini if 
 * no parameters are given.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Behavior
 * @lastmodified $Date: $
 */
class Garp_Model_Behavior_Browsable extends Garp_Model_Behavior_Abstract {
	/**
	 * Name of the virtual column containing the URL
	 * @var String
	 */
	const VIRTUAL_COLUMN_NAME = 'href';


	/**
	 * A sprintf() compatible string representing the URL to the record.
	 * @var String
	 */
	protected $_url;


	/**
	 * Parameters used to format self::_url.
	 * @var Array
	 */
	protected $_params;


	/**
	 * Configuration.
	 * @return Void
	 */
	protected function _setup($config) {
		if (!empty($config['url'])) {
			$this->_url = $config['url'];
		}
		if (!empty($config['params'])) {
			$this->_params = $config['params'];
		}
	}


	/**
	 * After fetch callback
	 * @param Array $args
	 * @return Void
	 */
	public function afterFetch(Array &$args) {
		$model = $args[0];
		$results = $args[1];
		$table = $model->getName();
		$view = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('view');

		if (!$results instanceof Garp_Db_Table_Rowset) {
			$results = array($results);
		}

		$url = $this->_url;
		$params = (array)$this->_params;
		// set sensible defaults
		if (!$url) {
			// non-intelligent route existence check:
			if (strpos(file_get_contents(APPLICATION_PATH.'/configs/routes.ini'),
					   '/'.$model->getName().'/:slug')
			   ) {
				$url = '/'.$model->getName().'/%s';
				$params = array('slug');
			} else {
				throw new Garp_Model_Behavior_Exception('Route discovery failed. Please add "/'.$model->getName().'/:slug" to'.
									' routes.ini or give a proper URL configuration to the behavior.');
			}
		}

		foreach ($results as $row) {
			if (!$row) {
				continue;
			}
			// Maps parameters to column values. $params should contain column names.
			$columnToValue = function($column) use ($row) {
				try {
					return $row->{$column};
				} catch (Exception $e) {
					return '';
				}
			};

			$values = array_map($columnToValue, $params);
			array_unshift($values, $url);
			$theUrl = call_user_func_array('sprintf', $values);
			$theUrl = $view->fullUrl($theUrl);

			$row->setVirtual(
				self::VIRTUAL_COLUMN_NAME,
				$theUrl
			);
		}

		// return the pointer to 0
		if ($results instanceof Garp_Db_Table_Rowset) {
			$results->rewind();
		} else {
		// also, return results to the original format if it was no Rowset to begin with.
			$results = $results[0];
		}
	}
}
