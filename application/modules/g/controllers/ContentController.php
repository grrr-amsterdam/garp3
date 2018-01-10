<?php
/**
 * G_ContentController
 * This controller handles content managing actions. The usual crud;
 * fetch, create, update, delete
 *
 * @package Garp
 * @subpackage Controllers
 * @author Harmen Janssen <harmen@grrr.nl>
 * @author David Spreekmeester <david@grrr.nl>
 */
class G_ContentController extends Garp_Controller_Action {
    /**
     * Key indicating any file type is allowed
     */
    const UPLOAD_TYPE_ALL = 'all';

    /**
     * Called before all actions
     *
     * @return Void
     */
    public function init() {
        // Do not cache CMS pages. This prevents a common situation where people logout, return to
        // the CMS, and see the interface but none of the content feeds load. Only after a browser
        // refresh they'll get bounced to the login page.
        $this->_helper->cache->setNoCacheHeaders($this->getResponse());

        $config = Zend_Registry::get('config');
        $this->_setCmsClosedMessage();
        if (!$config->cms || !$config->cms->ipfilter || !count($config->cms->ipfilter->toArray())) {
            return true;
        }
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
        if ($ip === '127.0.0.1') {
            // i mean come on
            return true;
        }
        if (!in_array($ip, $config->cms->ipfilter->toArray())) {
            $authVars = Garp_Auth::getInstance()->getConfigValues();
            $this->_helper->flashMessenger(__($authVars['noPermissionMsg']));
            $this->_helper->redirector->gotoRoute(array(), $authVars['login']['route']);
            return false;
        }
    }

    /**
     * Test page
     *
     * @return Void
     */
    public function indexAction() {
        $this->view->title = 'Garp API Console';
    }

    /**
     * Reset OpCache
     *
     * @return Void
     */
    public function opcacheresetAction() {
        $this->_helper->viewRenderer->setNoRender(true);
        if (function_exists('opcache_reset')) {
            $result = opcache_reset();
            echo $result ? 'opcache resetted' : 'opcache reset failed';
            return;
        }
        echo 'no opcache installed';
    }

    /**
     * Display some information about cookies
     *
     * @return Void
     */
    public function cookiesAction() {
        $config       = Zend_Registry::get('config');
        $viewBasePath = $config->resources->view->basePath ?:
            APPLICATION_PATH . '/modules/default/views';

        $this->_helper->layout->setLayoutPath($viewBasePath . '/layouts/');
        $this->view->title = 'Cookies';
    }

    /**
     * Landing page
     *
     * @return Void
     */
    public function adminAction() {
        $ini = Zend_Registry::get('config');
        $pageTitle = 'Garp CMS';
        if (!empty($ini->app->name)) {
            $pageTitle .= ' | ' . $ini->app->name;
        }

        $this->view->imagesCdn = $this->view->assetUrl('') . $ini->cdn->path->upload->image . '/';
        $this->view->documentsCdn = $this->view->assetUrl('') .
            $ini->cdn->path->upload->document . '/';

        $this->view->title = $pageTitle;
        $this->view->locale = $ini->resources->locale->default;
        $this->_helper->layout->setLayout('admin-layout');
    }

    /**
     * Image browser (trimmed down version of Garp CMS)
     *
     * @return Void
     */
    public function imagebrowserAction() {
        $this->adminAction();
        $this->view->isImageBrowser = true;
        $this->renderScript('content/admin.phtml');
    }

    /**
     * JSON-RPC entrance.
     *
     * @return Void
     */
    public function apiAction() {
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
            $requests = Zend_Json::decode($post, Zend_Json::TYPE_ARRAY);
            /**
             * Check if this was a batch request. In that case the array is a plain array of
             * arrays. If not, there will be a 'jsonrpc' key in the root of the array.
             */
            $batch = !array_key_exists('jsonrpc', $requests);
            if (!$batch) {
                $requests = array($requests);
            }
            foreach ($requests as $i => $request) {
                $request = $this->_reformJsonRpcRequest($request);
                $requestJson = Zend_Json::encode($request);
                $requestObj = new Zend_Json_Server_Request();
                $requestObj->loadJson($requestJson);
                $server->setRequest($requestObj);
                /**
                 * Note; response gets returned by reference, resulting in a $responses array
                 * containing all the same items.
                 * That's why clone is used here.
                 */
                $response = clone $server->handle();
                $responses[] = $response;
            }
            $response = $batch ? '[' . implode(',', $responses) . ']' : $responses[0];
        } else {
            $response = $server->getServiceMap();
        }
        $this->_helper->layout->setLayout('json');
        //  filter out escaped slashes, because they're annoying and not necessary.
        $response = str_replace('\/', '/', $response);
        $this->view->response = $response;
    }

    /**
     * Upload a file.
     *
     * @return Void
     */
    public function uploadAction() {
        $request = $this->getRequest();
        if (!$uploadType = $request->getParam('type')) {
            throw new Exception('When uploading files, \'type\' is a required parameter.');
        }

        if ($uploadType == self::UPLOAD_TYPE_ALL) {
            $uploadType = Garp_File::TYPE_DOCUMENTS;
            $filename = $request->getParam('filename');
            if (!$filename) {
                throw new Exception('When using type "all" a filename must be specified.');
            }
            $dotPos = strpos($filename, '.');
            if ($dotPos !== false) {
                $extension = substr($filename, $dotPos+1);
                $gif = new Garp_Image_File();
                if (in_array($extension, $gif->getAllowedExtensions())) {
                    $uploadType = Garp_File::TYPE_IMAGES;
                }
            }
        }
        $response   = array();
        $success    = false;
        if ($request->isPost()) {
            // Upload the file
            if ('raw' === $request->getParam('mode')) {
                $rawPostData = $request->getRawBody();
                $filename = $request->getParam('filename');
                if (!$filename) {
                    throw new Exception('When using raw upload-mode a filename must be specified.');
                }
                $response = $this->_helper->upload->uploadRaw($uploadType, $filename, $rawPostData);
                $success = true;
            } else {
                try {
                    $response = $this->_helper->upload(
                        $uploadType, null, false,
                        1 === (int)$this->getRequest()->getParam('overwrite')
                    );
                    $success = true;
                } catch (Exception $e) {
                    $response['messages'] = $e->getMessage();
                }
            }
            // Also create a new record for the uploaded file
            if ($request->getParam('insert') && $request->getParam('insert')) {
                $modelClass = Garp_File::TYPE_IMAGES == $uploadType ? 'Model_Image' :
                    'Model_Document';
                // create new record here...
                $model = new $modelClass();
                $model->setCmsContext(true);
                $_response = array();
                foreach ($response as $key => $value) {
                    // @todo Add method that allows the other columns of $model
                    // to be set via POST.
                    $data = array('filename' => $value);
                    if ($uploadType === Garp_File::TYPE_DOCUMENTS) {
                        // Create name from basename without extension.
                        $name = substr($value, 0, strrpos($value, '.'));
                        $data['name'] = $name;
                    }
                    $primary = $model->insert($data);
                    // Alter response a little to include columns
                    $_response[$key] = array(
                        'id' => $primary,
                        'filename' => $value
                    );
                }
                $response = $_response;
            }
        } else {
            throw new Exception('No POST data received.');
        }

        $response['success'] = $success;
        $this->_helper->layout->setLayout('blank');
        $this->view->response = $response;
    }

    /**
     * Download an uploaded file
     *
     * @return Void
     */
    public function downloadAction() {
        $ini = Zend_Registry::get('config');
        $downloadType = $this->getRequest()->getParam('downloadType') ?: Garp_File::TYPE_DOCUMENTS;
        $uploadOrStatic = $this->getRequest()->getParam('uploadOrStatic') ?: 'upload';
        $file = $this->getRequest()->getParam('file');

        if (!$file) {
            throw new Zend_Controller_Action_Exception('Geen bestandsnaam opgegeven.', 404);
        }

        try {
            $fileHandler = new Garp_File($downloadType, $uploadOrStatic);
            $url = $fileHandler->getUrl($file);
            $this->_downloadFile($url);
        } catch (Garp_File_Exception_InvalidType $e) {
            // Just throw a 404, since the error is basically just a wrong URL.
            throw new Zend_Controller_Action_Exception($e->getMessage(), 404);
        }
    }

    /**
     * /g/content/download-zipped/dinges.pdf,jadda.gif/myZipArchiveName
     *
     * @return Void
     */
    public function downloadzippedAction() {
        $params = $this->getRequest()->getParams();

        if (!array_key_exists('files', $params)
            || !array_key_exists('zipname', $params)
            || !$params['files']
            || !$params['zipname']
        ) {
            throw new Exception('Please provide a filename list, and a name for the archive.');
        }

        $zip = new ZipArchive();
        $tmpDir = sys_get_temp_dir();
        $tmpDir .= $tmpDir[strlen($tmpDir) - 1] !== '/' ? '/' : '';
        $zipPath = $tmpDir . $params['zipname'] . '.zip';

        if ($zip->open($zipPath, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE) !== true) {
            throw new Exception("Cannot open <$zipPath>\n");
        }

        //  @todo: als cdn niet lokaal is, moet je waarschijnlijk met zip::addFromString werken.
        $filenames = explode(',', $params['files']);

        $image = new Garp_Image_File(Garp_File::FILE_VARIANT_UPLOAD);
        $document = new Garp_File(null, Garp_File::FILE_VARIANT_UPLOAD);
        $ini = Zend_Registry::get('config');
        $cdnIsLocal = $ini->cdn->type === "local";

        foreach ($filenames as $filename) {
            $allowedImageExtensions = $image->getAllowedExtensions();
            $filenameParts = explode('.', $filename);
            $extension = $filenameParts[count($filenameParts) - 1];

            $fileIsImage = in_array($extension, $allowedImageExtensions);

            $file = $fileIsImage ? $image : $document;

            $url = $file->getUrl($filename);
            if ($content = @file_get_contents($url)) {
                $zip->addFromString('/' . $params['zipname'] . '/' . $filename, $content);
            } else {
                throw new Exception("{$url} could not be opened for inclusion in the zip archive.");
            }
        }

        $zip->close();

        $this->_downloadFile($zipPath);
        @unlink($zipPath);
    }

    /**
     * Download a file
     *
     * @param String $path Local path to a file, or a url.
     * @return Void
     */
    protected function _downloadFile($path) {
        // Push up memory_limit to deal with large files
        $mem = new Garp_Util_Memory();
        $mem->useHighMemory();

        $bytes = @file_get_contents($path);
        if (!$bytes) {
            throw new Zend_Controller_Action_Exception('File not found.', 404);
        }

        $download = Zend_Controller_Action_HelperBroker::getStaticHelper('download');
        $download->force($bytes, basename($path), $this->_response);

        Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);
        $this->_helper->viewRenderer->setNoRender();
    }

    /**
     * Import content from various formats.
     * This action has two states;
     * - first a datafile is uploaded. The user is presented with a mapping interface
     *   where they have to map columns in the datafile to columns in the database.
     * - then this URL is called again with the selected mapping, and the columns are
     *   mapped and inserted into the database.
     *
     * @return Void
     */
    public function importAction() {
        $memLim = ini_get('memory_limit');
        ini_set('memory_limit', '2G');
        set_time_limit(0); // No time limit

        $params = new Garp_Util_Configuration($this->getRequest()->getParams());
        $params->obligate('datafile')
            ->obligate('model')
            ->setDefault('firstRow', 0)
            ->setDefault('ignoreErrors', false);
        $importer = Garp_Content_Import_Factory::getImporter($params['datafile']);
        $success = false;
        if (isset($params['mapping'])) {
            $mapping = Zend_Json::decode($params['mapping']);

            $className = Garp_Content_Api::modelAliasToClass($params['model']);
            $model = new $className();
            $model->setCmsContext(true);
            $response = array();
            try {
                $success    = !!$importer->save(
                    $model, $mapping, array(
                    'firstRow' => $params['firstRow'],
                    'ignoreErrors' => $params['ignoreErrors'],
                    )
                );
            } catch (Exception $e) {
                $response['message'] = $e->getMessage();
            }

            if ($success) {
                // cleanup input file
                $gf = new Garp_File();
                $gf->remove($params['datafile']);
            }

            $response['success'] = $success;
            $this->view->response = $response;
        } else {
            $std = new stdClass();
            $std->success = true;
            $std->data = $importer->getSampleData();
            $this->view->response = $std;
        }
        ini_set('memory_limit', $memLim);
        $this->_helper->layout->setLayout('json');
        $this->renderScript('content/call.phtml');
    }

    /**
     * Export content in various formats
     *
     * @return Void
     */
    public function exportAction() {
        $mem = new Garp_Util_Memory();
        $mem->useHighMemory();

        $params = new Garp_Util_Configuration($this->getRequest()->getParams());
        // make sure some required parameters are in place
        $params->obligate('exporttype')
            ->obligate('model')
            ->obligate('selection')
            ->setDefault('fields', Zend_Db_Select::SQL_WILDCARD);

        // fetch exporter
        $exporter = Garp_Content_Export_Factory::getExporter($params['exporttype']);
        $bytes = $exporter->getOutput($params);
        $filename = $exporter->getFilename($params);

        $download = Zend_Controller_Action_HelperBroker::getStaticHelper('download');
        $download->force($bytes, $filename, $this->_response);
        $this->_helper->viewRenderer->setNoRender();
    }

    /**
     * Clear all cache system wide.
     * Static Cache is tagged, so a comma-separated list of tags may be given to
     * only clear cache tagged with those tags.
     * Memcache is not tagged.
     *
     * @return Void
     */
    public function clearcacheAction() {
        $request = $this->getRequest();
        $tags = array();
        if ($request->getParam('tags')) {
            $tags = explode(',', $request->getParam('tags'));
        }
        $createClusterJob = is_null($request->getParam('createClusterJob')) ? 1 :
            $request->getParam('createClusterJob');

        $this->view->title = 'Clear that cache';
        Garp_Cache_Manager::purge($tags, $createClusterJob);
    }

    /**
     * Shortcut to phpinfo
     *
     * @return Void
     */
    public function infoAction() {
        phpinfo();
        // This might be the only time `exit()` is appropriate...
        // If only there was a pure function that *returned* this information instead of vomiting it
        // on screen. Alas!
        // In the future someone might opt to go all ob_start() on this bitch but for now it's just
        // not worth the trouble.
        // @codingStandardsIgnoreStart
        exit;
        // @codingStandardsIgnoreEnd
    }

    /**
     * Show datamodel
     *
     * @todo: refactor naar nieuwe Spawn opbouw.
     * @return Void
     */
    public function datamodelAction() {
        $this->view->models = Garp_Spawn_Model_Set::getInstance();

        $request = $this->getRequest();
        $params = $request->getParams();

        if (array_key_exists('text', $params)) {
            $this->view->textMode = true;
            $this->_helper->layout->setLayout('blank');
        } else {
            $this->_helper->layout->setLayout('datamodel-layout');
        }
    }

    /**
     * Edit request so everything passes thru the Garp_Content_Manager_Proxy::pass
     *
     * @param Array $request The original request
     * @return Array
     */
    protected function _reformJsonRpcRequest($request) {
        /**
         * Sanity check, if the right properties are not found in the object,
         * pass the original along to Zend_Json_Server and let it fail.
         */
        if (!is_array($request)
            || !array_key_exists('method', $request)
            || !array_key_exists('params', $request)
        ) {
            return $request;
        }
        $meth = $request['method'];
        $meth = explode('.', $meth);
        // send everything thru Garp_Content_Manager_Proxy::pass
        $request['method'] = 'pass';
        $request['params'] = array($meth[0], $meth[1], $request['params']);
        return $request;
    }

    /**
     * Retrieve POSTed JSON-RPC request
     *
     * @return String
     */
    protected function _getJsonRpcRequest() {
        if ($this->getRequest()->getPost('request')) {
            return $this->getRequest()->getPost('request');
        }
        return $request = $this->getRequest()->getRawBody();
    }

    protected function _setCmsClosedMessage() {
        $config = Zend_Registry::get('config');
        if (!isset($config->cms->closed) || !$config->cms->closed) {
            return;
        }
        $this->view->isClosed = true;
        try {
            $snippetModel = new Model_Snippet();
            if ($snippetModel->isMultilingual()) {
                $snippetModel = instance(new Garp_I18n_ModelFactory)->getModel('Snippet');
            }
            $snippet = $snippetModel->fetchByIdentifier('cms closed message');
            $cmsClosedMessage = $snippet->html;
            if (isset($cmsClosedMessage[Garp_I18n::getCurrentLocale()])) {
                $cmsClosedMessage = $cmsClosedMessage[Garp_I18n::getCurrentLocale()];
            }
            $this->view->cmsClosedMessage = $cmsClosedMessage;
        } catch (Exception $e) {
            throw $e;
            $this->view->cmsClosedMessage = '<p>The CMS is closed.</p>';
        }
    }
}
