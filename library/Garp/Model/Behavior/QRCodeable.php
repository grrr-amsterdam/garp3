<?php
/**
 * Garp_Model_Behavior_QRCodeable
 * Generates a QR Code that contains the url to this object.
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Behavior
 * @lastmodified $Date: $
 */
class Garp_Model_Behavior_QRCodeable extends Garp_Model_Behavior_Abstract {
	/**
	 * Fields to work on
	 * @var Array
	 */
	protected $_fields;


	/**
	 * Make sure the config array is at least filled with some default values to work with.
	 * @param Array $config Configuration values
	 * @return Array The modified array
	 */
	protected function _setup($config) {
		$this->_fields = $config;
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
		$chartService = new Garp_Service_Google_Chart();
		$ini = Garp_Cache_Ini::factory(APPLICATION_PATH.'/configs/routes.ini');
		$routes = $ini->routes->toArray();
		$view = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('view');

		$routeFound = false;
		foreach ($routes as $r) {
			if (
				array_key_exists('route', $r) &&
				$r['route'] === '/'.$table.'/:slug'
			) {
				$routeFound = $r;
				break;
			}
		}

		if (!$routeFound) {
			throw new Exception('There is no direct route to this object.');
		}

		// provide uniform interface, so we can always loop
		if (!$results instanceof Garp_Db_Table_Rowset) {
			$results = array($results);	
		}

		foreach ($results as $row) {
			if (
				isset($row->slug) &&
				$row->slug
			) {
				$refUrl = $view->fullUrl('/'.$table.'/'.$row->slug);
				$row->setVirtual('qrcode', $chartService->fetchQRCodeUrl($refUrl));
			}
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
