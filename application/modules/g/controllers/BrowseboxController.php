<?php
/**
 * G_BrowseboxController
 * This controller receives paging requests from AJAX.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class G_BrowseboxController extends Garp_Controller_Action {
	/**
	 * Central entry point.
	 * @return Void
	 */
	public function indexAction() {
		$request = $this->getRequest();
		if (!$request->getParam('id') || !$request->getParam('chunk')) {
			throw new Exception('Not enough parameters: "id" and "chunk" are required.');
		}
		$bb = $this->_initBrowsebox($request);
		$this->view->bb = $bb;
		$this->_helper->layout->setLayout('blank');
	}
	
	
	/**
	 * Fetch a Browsebox object configured based on parameters found in the request.
	 * @param Zend_Controller_Request_Abstract $request The current request
	 * @return Garp_Browsebox
	 */
	protected function _initBrowsebox(Zend_Controller_Request_Abstract $request) {
		$bb = Garp_Browsebox::factory($request->getParam('id'));

		if ($request->getParam('conditions')) {
			$options = unserialize(base64_decode($request->getParam('conditions')));
			if (!empty($options['filters'])) {
				$conditions = base64_decode($options['filters']);
				$conditions = explode(Garp_Browsebox::BROWSEBOX_QUERY_FILTER_SEPARATOR, $conditions);
				foreach ($conditions as $condition) {
					$parts = explode(':', $condition);
					if (count($parts) < 2) {
						continue;
					}
					$filterId = $parts[0];
					$params   = explode(Garp_Browsebox::BROWSEBOX_QUERY_FILTER_PROP_SEPARATOR, $parts[1]);
					$bb->setFilter($filterId, $params);
				}
			}

			unset($options['filters']);
			foreach ($options as $key => $value) {
				$bb->setOption($key, $value);
			}
		}
		
		$bb->init($request->getParam('chunk'));
		return $bb;
	}
}
