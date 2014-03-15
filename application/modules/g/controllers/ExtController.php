<?php
/**
 * G_ExtController
 * This controller handles Ext.Direct requests and sends 'em off to either the appropriate 
 * models or the ContentController, which acts as a wrapper around crud functionality.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Controllers
 * @lastmodified $Date: $
 */
require 'ContentController.php';
class G_ExtController extends G_ContentController {
	/**
	 * Specify how to modify the results after the JSON-RPC handling. (Array because batches are possible)
	 * @var Array
	 */
	protected $_postModifyMethods = array();

	/**
	 * Sometimes additional data from the original request is needed to properly modify the response.
	 * Store the original request here. (Array because batches are possible)
	 * @var Array
	 */
	protected $_originalRequests = array();

	public function init() {
		$this->_helper->cache(array(
			'smd', 'closevimeologin', 'getlocale'
		));
	}

	/**
	 * Generate Ext.Direct API, publishing allowed actions to Ext.Direct, according to Service Mapping Description JSON-RPC protocol.
	 * @return Void
	 */
	public function smdAction() {
		$this->_helper->layout->setLayout('json');
		$api = new Garp_Content_Api();
		$this->view->api = $api->getLayout();
	}

	/**
	 * Peter considered this docblock not important.
	 */
	public function closevimeologinAction() {
	}

	/**
 	 * Get translation table
 	 * @return Void
 	 */
	public function getlocaleAction() {
		if (!Zend_Registry::isRegistered('Zend_Translate')) {
			throw new Zend_Controller_Action_Exception('Zend_Translate was not registered in Zend_Registry.', 500);
		}
		$translate = Zend_Registry::get('Zend_Translate');
		$messages = $translate->getMessages();
		$this->view->messages = $messages;
		$this->_helper->layout->setLayout('json');
	}

	/**
	 * Callback called before executing action.
	 * Transforms requests to a format ExtJs accepts.
	 * Also turns off caching, since the CMS always receives live data.
	 * @return Void
	 */
	public function preDispatch() {
		if ($this->getRequest()->isPost()) {
			$post = $this->_getJsonRpcRequest();
			$requests = Zend_Json::decode($post, Zend_Json::TYPE_ARRAY);
			$modifiedRequests = array();
			/**
 			 * Check if this was a batch request. In that case the array is a plain array of 
 			 * arrays. If not, there will be a 'jsonrpc' key in the root of the array.
 			 */
			$batch = !array_key_exists('jsonrpc', $requests);
			if (!$batch) {
				$requests = array($requests);
			}
			foreach ($requests as $i => $request) {
				$this->_saveRequestForModification($request, $i);
				if (array_key_exists('method', $request)) {
					$methodParts = explode('.', $request['method']);
					$method = array_pop($methodParts);
					if (in_array($method, array('create', 'update', 'destroy'))) {
						$modifyMethod = '_modifyBefore'.ucfirst($method);
						$request = $this->{$modifyMethod}($request);
					}
				}
				$modifiedRequests[] = $request;
			}
			if ($batch) {
				$this->getRequest()->setPost('request', Zend_Json::encode($modifiedRequests));
			} else {
				$this->getRequest()->setPost('request', Zend_Json::encode($modifiedRequests[0]));
			}
		}
		parent::preDispatch();
		$this->_toggleCache(false);
	}

	/**
	 * Callback called after executing action.
	 * Transforms requests to a format ExtJs accepts.
	 * Also turns on caching, because this request might be chained.
	 * @return Void
	 */
	public function postDispatch() {
		parent::postDispatch();
		if (!empty($this->_postModifyMethods)) {
			$response = Zend_Json::decode($this->view->response, Zend_Json::TYPE_ARRAY);
			$modifiedResponse = array();
			/**
 			 * Check if this was a batch request. In that case the array is a plain array of 
 			 * arrays. If not, there will be a 'jsonrpc' key in the root of the array.
 			 */
			$batch = !array_key_exists('jsonrpc', $response);
			if (!$batch) {
				$response = array($response);
			}
			/**
 			 * Post modify methods are recorded from $this:preDispatch()
 			 */
			foreach ($this->_postModifyMethods as $i => $postMethod) {
				if (!empty($response[$i]) && !empty($this->_originalRequests[$i])) {
					$modifyMethod = '_modifyAfter'.ucfirst($postMethod);
					$response[$i] = $this->{$modifyMethod}($response[$i], $this->_originalRequests[$i]);
				}
			}
			if ($batch) {
				$this->view->response = Zend_Json::encode($response);
			} else {
				$this->view->response = Zend_Json::encode($response[0]);
			}
		}
		$this->_toggleCache(true);
	}

	/**
	 * Save a request preDispatch for modification postDispatch
	 * @param Array $request The request
	 * @param Int $i The index under which the request should be filed in the array
	 * @return Void
	 */
	protected function _saveRequestForModification($request, $i) {
		if (array_key_exists('method', $request)) {
			$methodParts = explode('.', $request['method']);
			$method = array_pop($methodParts);
			if (in_array($method, array('fetch', 'create', 'update', 'destroy'))) {
				$this->_postModifyMethods[$i] = $method;
				$this->_originalRequests[$i] = $request;
			}
		}
	}

	/**
	 * Fetch total amount of records condoning to a set of conditions
	 * @param String $modelName The entity name
	 * @param Array $conditions 
	 * @return Int
	 */
	protected function _fetchTotal($modelName, $conditions) {
		if (!array_key_exists('query', $conditions)) {
			$conditions['query'] = array();
		}
		$man = new Garp_Content_Manager($modelName);
		return $man->count($conditions);
	}

	/**
	 * Check if the JSON-RPC request failed
	 * @param Array $obj The response object (json_decoded)
	 * @return Boolean
	 */
	protected function _methodFailed($response) {
		return !empty($response['error']);
	}

	/**
	 * Toggle cache on and off
	 * @param Boolean $on
	 * @return Boolean Wether the cache is on or off
	 */
	protected function _toggleCache($on) {
		//Zend_Registry::set('readFromCache', $on);
		return $on;
	}

	/**
	 * MODIFICATION METHODS
	 * these methods modify either the request or the response (post or pre dispatch)
	 * ------------------------------------------------------------------------------
	 */
	
	/**
	 * Modify results after fetch
	 * @param Array $response The original response
	 * @param Array $request The original request
	 * @return String
	 */
	protected function _modifyAfterFetch($response, $request) {
		if (!$this->_methodFailed($response)) {
			$rows = $response['result'];
			$methodParts = explode('.', $request['method']);
			$modelClass  = Garp_Content_Api::modelAliasToClass(array_shift($methodParts));
			
			$params = $request['params'];
			$params = !empty($params) ? $params[0] : array();
			$response['result'] = array(
				'rows' => $rows,
				'total' => $this->_fetchTotal($modelClass, $params)
			);
		}
		return $response;
	}

	/**
	 * Modify results after create
	 * @param Array $response The original response
	 * @param Array $request The original request
	 * @return String
	 */
	protected function _modifyAfterCreate($response, $request) {
		if (!$this->_methodFailed($response)) {
			// figure out model name (request.method)
			$methodParts = explode('.', $request['method']);
			$modelClass  = Garp_Content_Api::modelAliasToClass(array_shift($methodParts));
			$model = new $modelClass();
			// combine primary keys to query params
			$primaryKey = array_values((array)$model->info(Zend_Db_Table::PRIMARY));			
			$primaryKeyValues = array_values((array)$response['result']);
			$query = array();
			foreach ($primaryKey as $i => $key) {
				$query[$key] = $primaryKeyValues[$i];
			}		
			// find the newly inserted rows
			$man = new Garp_Content_Manager(get_class($model));
			$rows = $man->fetch(array('query' => $query));
			$response['result'] = array(
				'rows' => $rows
			);
		}
		return $response;
	}

	/**
	 * Modify results after update
	 * @param Array $response The original response
	 * @param Array $request The original request
	 * @return String
	 */
	protected function _modifyAfterUpdate($response, $request) {
		if ($this->_methodFailed($response)) {	
			return $response;
		}
		$methodParts = explode('.', $request['method']);
		$modelClass  = Garp_Content_Api::modelAliasToClass(array_shift($methodParts)); 
		$man = new Garp_Content_Manager($modelClass);
		$rows = $man->fetch(array(
			'query' => array('id' => $request['params'][0]['rows']['id'])
		));
		$response['result'] = array(
			'rows' => $rows
		);
		return $response;
	}

	/**
	 * Modify results after destroy
	 * @param Array $response The original response
	 * @param Array $request The original request
	 * @return String
	 */
	protected function _modifyAfterDestroy($response, $request) {
		if (!$this->_methodFailed($response)) {
			$response['result'] = array(
				'rows' => array()
			);
		}
		return $response;
	}

	/**
	 * Modify results before create
	 * @param Array $request
	 * @return Array 
	 */
	protected function _modifyBeforeCreate($request) {
		$request['params'] = array($request['params'][0]['rows']);
		return $request;
	}

	/**
	 * Modify results before update
	 * @param Array $request
	 * @return Array 
	 */
	protected function _modifyBeforeUpdate($request) {
		$request['params'] = array($request['params'][0]['rows']);
		return $request;
	}

	/**
	 * Modify results before create
	 * @param Array $request
	 * @return Array 
	 */
	protected function _modifyBeforeDestroy($request) {
		$request['params'] = array(array('id' => $request['params'][0]['rows']));
		return $request;
	} 
}
