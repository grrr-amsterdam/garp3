<?php
/**
 * G_ContentController
 * This controller handles content managing actions. The usual crud; 
 * fetch, create, update, delete
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Controllers
 * @lastmodified $Date: $
 */
class G_ContentController extends Garp_Controller_Action {
	/**
	 * Test page
	 * @return Void
	 */
	public function indexAction() {
		$this->view->title = 'Garp API Console';
	}
	
	
	/**
	 * Landing page
	 * @return Void
	 */
	public function adminAction() {
		Zend_Registry::set('CMS', true);
				
		$ini = new Zend_Config_Ini(APPLICATION_PATH.'/configs/application.ini', APPLICATION_ENV);
		$pageTitle = 'Garp CMS';
		if (!empty($ini->app->name)) {
			$pageTitle .= ' | '.$ini->app->name;
		}

		$this->view->imagesCdn = 'http://'.$ini->cdn->domain.$ini->cdn->path->upload->image.'/';
		$this->view->documentsCdn = 'http://'.$ini->cdn->domain.$ini->cdn->path->upload->document.'/';

		$this->view->title = $pageTitle;
		$this->view->locale = $ini->resources->locale->default;
		$this->_helper->layout->setLayout('admin');
	}
	
	
	/**
	 * JSON-RPC entrance.
	 * @return Void
	 */
	public function apiAction() {
		Zend_Registry::set('CMS', true);
		/**
		 * Prepare the server. Zend_Json_Server cannot work with batched requests natively, 
		 * so that's taken care of customly here. Therefore, autoEmitResponse is set to false
		 * so the server doesn't print the response directly.
		 */
		$server = new Zend_Json_Server();
		$server->setClass('Garp_Content_Manager_Proxy');
		$server->setAutoEmitResponse(false);

		if ($this->getRequest()->isPost()) {
			$post = $this->_getJsonRpcRequest();
			$batch = false;
			$responses = array();
			$requests = Zend_Json::decode($post, Zend_Json::TYPE_OBJECT);
			if (!is_array($requests)) {
				$requests = array($requests);
			} else {
				$batch = true;
			}
			foreach ($requests as $i => $request) {
				$request = $this->_reformJsonRpcRequest($request);
				$requestJson = Zend_Json::encode($request);
				$requestObj = new Zend_Json_Server_Request();
				$requestObj->loadJson($requestJson);
				$server->setRequest($requestObj);
				/**
				 * Note; response gets returned by reference, resulting in a $responses array containing all the same items.
				 * That's why clone is used here.
				 */
				$response = clone $server->handle();
				$responses[] = $response;
			}
			$response = $batch ? '['.implode(',', $responses).']' : $responses[0];
		} else {
			$response = $server->getServiceMap();
		}
		$this->_helper->layout->setLayout('json');
		//	filter out escaped slashes, because they're annoying and not neccessary.
		$response = str_replace('\/', '/', $response);
		$this->view->response = $response;
	}
	
	
	/**
	 * Upload a file.
	 * @return Void
	 */
	public function uploadAction() {
		Zend_Registry::set('CMS', true);
		$request = $this->getRequest();
		$uploadType = $request->getParam('type');

		$response = array('success' => false);
		if ($request->isPost()) {
			$file = new Garp_File($uploadType);
			$errors = array();

			foreach ($_FILES as $formKey => $fileParams) {
				try {
					if (is_uploaded_file($fileParams['tmp_name'])) {
						if (
							$newFilename = $file->store(
								$fileParams['name'],
								file_get_contents($fileParams['tmp_name'])
							)
						) {
							$response[$formKey] = $newFilename;
						}
					} else throw new Exception('This is not an uploaded file.');
				} catch (Exception $e) {
					$errors[] = "Could not store {$fileParams['name']}. ".$e->getMessage();
				}
			}

			if ($errors)
				$response['messages'] = $errors;
			else
				$response['success'] = true;
		} else {
			throw new Exception('No POST data received.');
		}

		$this->_helper->layout->setLayout('blank');
		$this->view->response = $response;
	}

		
	/**
	 * Download an uploaded file
	 * @return Void
	 */
	public function downloadAction() {
		// note; Garp_Cache_Config is not used here because we always want fresh data in the CMS, 
		// no cached versions
		$ini = new Zend_Config_Ini(APPLICATION_PATH.'/configs/application.ini', APPLICATION_ENV);
		$uploadType = $this->getRequest()->getParam('downloadType');
		$file = $this->getRequest()->getParam('file');

		if (!$file) {
			throw new Zend_Controller_Action_Exception('Geen bestandsnaam opgegeven.', 404);
		}

		$fileHandler = new Garp_File($uploadType ? : 'document', 'upload');

		// Push up memory_limit to deal with large files
		$memLim = ini_get('memory_limit');
		ini_set('memory_limit', '256M');

		// Process the file
		$bytes = @file_get_contents($fileHandler->getUrl($file));
		$download = Zend_Controller_Action_HelperBroker::getStaticHelper('download');
		$download->force($bytes, basename($file), $this->_response);

		// reset memory limit
		ini_set('memory_limit', $memLim);
	}


	/**
	 * Import content from various formats.
	 * This action has two states; 
	 * - first a datafile is uploaded. The user is presented with a mapping interface
	 *   where they have to map columns in the datafile to columns in the database.
	 * - then this URL is called again with the selected mapping, and the columns are
	 *   mapped and inserted into the database.
	 * @return Void
	 */
	public function importAction() {
		Zend_Registry::set('CMS', true);
		$params = new Garp_Util_Configuration($this->getRequest()->getParams());
		$params->obligate('datafile')
			   ->obligate('model')
			   ->setDefault('firstRow', 0)
			   ->setDefault('ignoreErrors', false)
		;
		$importer = Garp_Content_Import_Factory::getImporter($params['datafile']);
		if (isset($params['mapping'])) {
			$mapping	= Zend_Json::decode($params['mapping']);
			
			$className	= Garp_Content_Api::modelAliasToClass($params['model']);
			$model		= new $className();
			$response = array();
			try {
				$success	= !!$importer->save($model, $mapping, array(
					'firstRow' => $params['firstRow'],
					'ignoreErrors' => $params['ignoreErrors'],
				));
			} catch (Exception $e) {
				$response['message'] = $e->getMessage();
			}
			$response['success'] = $success;
			$this->view->response = $response;
		} else {
			$std = new stdClass();
			$std->success = true;
			$std->data = $importer->getSampleData();
			$this->view->response = $std;
		}
		$this->_helper->layout->setLayout('json');
		$this->renderScript('content/call.phtml');
	}
	
	
	/**
	 * Export content in various formats
	 * @return Void
	 */
	public function exportAction() {
		Zend_Registry::set('CMS', true);
		$params = new Garp_Util_Configuration($this->getRequest()->getParams());
		// make sure some required parameters are in place
		$params->obligate('exporttype')
			   ->obligate('model')
			   ->obligate('fields')
			   ->obligate('selection')
		;
		
		// fetch exporter
		$exporter = Garp_Content_Export_Factory::getExporter($params['exporttype']);
		$bytes = $exporter->getOutput($params);
		$filename = $exporter->getFilename($params);
		
		$download = Zend_Controller_Action_HelperBroker::getStaticHelper('download');
		$download->force($bytes, $filename, $this->_response);		
	}
	
	
	/**
	 * Clear all cache system wide.
	 * Static Cache is tagged, so a comma-separated list of tags may be given to only clear cache tagged with those tags.
	 * Memcache is not tagged.
	 * @return Void
	 */
	public function clearcacheAction() {
		$request = $this->getRequest();
		$tags = array();
		if ($request->getParam('tags')) {
			$tags = explode(',', $request->getParam('tags'));
		}
		$createClusterJob = is_null($request->getParam('createClusterJob')) ? 1 : $request->getParam('createClusterJob');

		$this->view->title = 'Clear that cache';
		Garp_Cache_Manager::purge($tags, $createClusterJob);
	}
	
	
	/**
	 * Shortcut to phpinfo
	 * @return Void
	 */
	public function infoAction() {
		phpinfo();
		exit;
	}


	public function datamodelAction() {
		$this->view->models = Garp_Model_Spawn_Models::getInstance();
		
		$this->_helper->layout->setLayout('datamodel');
	}


	
	
	/**
	 * Edit request so everything passes thru the Garp_Content_Manager_Proxy::pass
	 * @param stdClass $request
	 * @return stdClass
	 */
	protected function _reformJsonRpcRequest($request) {
		/**
		 * Sanity check, if the right properties are not found in the object,
		 * pass the original along to Zend_Json_Server and let it fail.
		 */ 
		if (!is_object($request) || !property_exists($request, 'method') || !property_exists($request, 'params')) {
			return $request;
		}
		$meth = $request->method;
		$meth = explode('.', $meth);
		// send everything thru Garp_Content_Manager_Proxy::pass
		$request->method = 'pass';
		$request->params = array($meth[0], $meth[1], $request->params);
		return $request;
	}
		
	
	/**
	 * Retrieve POSTed JSON-RPC request
	 * @return String
	 */
	protected function _getJsonRpcRequest() {
		if ($this->getRequest()->getPost('request')) {
			return $this->getRequest()->getPost('request');
		}
		return $request = $this->getRequest()->getRawBody();
	}
	
	
	//DEPRECATED
	/**
	 * Get file uploader
	 * @return Zend_File_Transfer_Adapter_Http
	 */
	// protected function _getUploader($uploadType) {
	// 	// note; Garp_Cache_Config is not used here because we always want fresh data in the CMS, 
	// 	// no cached versions
	// 	$ini = new Zend_Config_Ini(APPLICATION_PATH.'/configs/application.ini', APPLICATION_ENV);
	// 	$uploadPath = $uploadType == 'image' ? $ini->image->path->upload : $ini->app->uploadsDirectory;
	// 
	// 	$upload = new Garp_File_Transfer_Adapter_Http();
	// 	$upload->setDestination($uploadPath);
	// 	// custom method, @see Garp_File_Transfer_Adapter_Http
	// 	$upload->renameDuplicates();
	// 	
	// 	// validators
	// 	$upload->addValidator('Count', true, array('min' => 1, 'max' => 10));
	// 	if ($ini->app->uploadableExtensions) {
	// 		$upload->addValidator('Extension', false, $ini->app->uploadableExtensions);
	// 	}
	// 	return $upload;
	// }
}
