<?php
/**
 * G_RestController
 * Provider of the REST API. Maps to Garp_Content_Api_Rest
 *
 * @package Garp
 * @subpackage Controllers
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class G_RestController extends Garp_Controller_Action {

    protected $_validMethods = array(
        'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'
    );

    public function init() {
        $this->_helper->cache->setNoCacheHeaders($this->getResponse());
        $this->_helper->layout->setLayoutPath(GARP_APPLICATION_PATH . '/modules/g/views/layouts');
        $this->_helper->layout->setLayout('json');

        if (strtoupper($this->getRequest()->getMethod()) === 'OPTIONS') {
            $this->getResponse()->setHeader(
                'Allow',
                implode(', ', $this->_validMethods)
            );
        }

        $this->getResponse()->setHeader('Access-Control-Allow-Origin', '*');
        $this->getResponse()->setHeader(
            'Access-Control-Allow-Methods',
            implode(', ', $this->_validMethods)
        );

        // My heart weeps for this disgusting global 😭
        Zend_Registry::set('CMS', true);
    }

    public function apiAction() {
        $method = strtolower($this->getRequest()->getMethod());
        $params = $this->getRequest()->getParams();

        try {
            $postData = $this->_parsePostData();
            $this->_validateMethod($method);

            $restApi = new Garp_Content_Api_Rest();
            $response = $restApi->{$method}($params, $postData);

            $this->view->result = $response['data'];

            if (!$response['render']) {
                $this->_helper->viewRenderer->setNoRender(true);
            }
            if ($response['httpCode']) {
                $this->_setHttpStatusCode($response['httpCode']);
            }
        } catch (Zend_Db_Statement_Exception $e) {
            // @todo Is this wise? I don't want to give a 500 error since it's not the server's
            // fault. But this might end up a big list of exceptions...
            // How to choose status 400 or 500 at runtime?
            $status = strpos($e->getMessage(), 'Duplicate entry') !== false ? 400 : 500;
            $this->_respondToError($e->getMessage(), $status);
        } catch (Garp_Content_Api_Rest_Exception $e) {
            $this->_respondToError($e->getMessage(), $e->getHttpStatusCode());
        } catch (Exception $e) {
            $this->_respondToError($e->getMessage(), 500);
        }
    }

    /**
     * Format a response object for the view, and set the right error code
     *
     * @param string $errorMessage
     * @param int $httpCode
     * @return void
     */
    protected function _respondToError($errorMessage, $httpCode) {
        $this->_setHttpStatusCode($httpCode);
        $this->view->result = array(
            'success' => false,
            'errorMessage' => $errorMessage
        );
    }

    protected function _setHttpStatusCode($httpCode) {
        $this->getResponse()->setHttpResponseCode($httpCode);
    }

    /**
     * Wether the HTTP method is valid
     *
     * @param string $method
     * @return bool
     */
    protected function _validateMethod($method) {
        if (in_array(strtoupper($method), $this->_validMethods)) {
            return true;
        }
        $exception = new Garp_Content_Api_Rest_Exception('Method not allowed');
        $exception->setHttpStatusCode(405);
        throw $exception;
    }

    /**
     * Wether response should be rendered.
     * "HEAD" requests only receive headers.
     *
     * @param string $method
     * @return bool
     */
    protected function _shouldRender($method) {
        return 'HEAD' !== $method;
    }

    /**
     * Parses post data as json
     *
     * @return array
     */
    protected function _parsePostData() {
        $postData = $this->getRequest()->getRawBody();
        if (!$postData) {
            return array();
        }
        try {
            $postData = Zend_Json::decode($postData);
        } catch (Zend_Json_Exception $e) {
            throw new Garp_Content_Api_Rest_Exception(
                sprintf(Garp_Content_Api_Rest::EXCEPTION_INVALID_JSON, $e->getMessage())
            );
        }
        return $postData;
    }
}
