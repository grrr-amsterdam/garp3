<?php 
/**
 * Controller fo handling OAuth2 requests
 * 
 * @package Garp
 * @author Ramiro Hammen <ramiro@grrr.nl>
 */
class G_OauthController extends Garp_Controller_Action {
    
    public function init() {
        $action = $this->getRequest()->getActionName();
        $this->_setViewSettings($action);
    }

    public function authorizeAction() {
        $client_id = $this->getRequest()->getParam('client_id');

        $this->view->client_id = $client_id;
        // $this->view->requestUri = $this->getRequest()->getReq;
        $this->view->requestUri = $this->getRequest()->getRequestUri();
       
        $storage = new Garp_OAuth2_Storage;
        $server = new OAuth2_Server($storage, array('allow_implicit' => true));
        $server->addGrantType(new OAuth2_GrantType_AuthorizationCode($storage));


        $request = OAuth2_Request::createFromGlobals();
        
        $response = new OAuth2_Response;

        if (!$server->validateAuthorizeRequest($request, $response)) {
            throw new Exception("Authorization Request Invalid", 1);
        }

        if ($this->getRequest()->isPost()) {
            $isAuthorized = ($this->getRequest()->getPost('authorized') === 'yes');
            
            $auth = Garp_Auth::getInstance();
            $userId = $auth->getUserId();
            $server->handleAuthorizeRequest($request, $response, $isAuthorized, $userId);
           
            if ($isAuthorized) {
                $code = substr(
                    $response->getHttpHeader('Location'), 
                    strpos($response->getHttpHeader('Location'), 'code=')+5
                );
                $this->view->response = array(
                    "success" => true,
                    "message" => "SUCCESS! Authorization Code: $code"
                );
            }
        }
    }

    public function userclientsAction() {
        // @todo add logic and view for adding oauth clients
    }

    public function tokenAction() {
         $this->_helper->layout->setLayout('json');

        $storage = new Garp_OAuth2_Storage;
        $server = new OAuth2_Server($storage);
        $server->addGrantType(new OAuth2_GrantType_ClientCredentials($storage));
        $response = new OAuth2_Response();
        $server->handleTokenRequest(OAuth2_Request::createFromGlobals(), $response);
        
        $this->view->response = array(
            'hola'
        );

    }

    protected function _setViewSettings($action) {

        $config = Zend_Registry::get('config');

        $oauthVars = $config->oauth->toArray();
        if (!isset($oauthVars[$action])) {
            return;
        }
        $oauthVars = $oauthVars[$action];
       
        $module = isset($oauthVars['module']) ? $oauthVars['module'] : 'default';
        $moduleDirectory = $this->getFrontController()
            ->getModuleDirectory($module);
        $viewPath = $moduleDirectory . '/views/scripts/';

        $this->view->addScriptPath($viewPath);
        $view = isset($oauthVars['view']) ? $oauthVars['view'] : $action;
        
        $this->_helper->viewRenderer($view);
        $layout = isset($oauthVars['layout']) ? $oauthVars['layout'] : 'layout';
        if ($this->_helper->layout->isEnabled()) {
            $this->_helper->layout->setLayoutPath($moduleDirectory . '/views/layouts');
            $this->_helper->layout->setLayout($layout);
        }
    }

    protected function _getAuthorizationForm() {
        $form = new Garp_OAuth2_AuthenticationForm;
        return $form;
    }
}