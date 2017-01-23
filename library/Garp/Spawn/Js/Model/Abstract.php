<?php
/**
 * Generated JS model
 *
 * @package Garp_Spawn
 * @author David Spreekmeester <david@grrr.nl>
 */
abstract class Garp_Spawn_Js_Model_Abstract {
    protected $_modelId;
    protected $_modelSet;

    public function __construct($modelId, Garp_Spawn_Model_Set $modelSet) {
        $this->_modelId = $modelId;
        $this->_modelSet = $modelSet;
    }

    public function render() {
        $view = $this->_getViewObject();

        $view->model = $this->_modelSet[$this->_modelId];
        $view->modelSet = $this->_modelSet;

        $view->setScriptPath(GARP_APPLICATION_PATH . '/modules/g/views/scripts/spawn/js/');
        return $view->render($this->_template);
    }

    /**
     * Beautify models file
     *
     * @param string $str The contents of the models file
     * @return string
     */
    protected function _beautify($str) {
        include_once GARP_APPLICATION_PATH .
            '/../library/Garp/3rdParty/JsBeautifier/jsbeautifier.php';
        return js_beautify($str);
    }

    /**
     * Minify models file
     *
     * @param string $str The contents of the models file
     * @return string
     */
    protected function _minify($str) {
        return \JShrink\Minifier::minify($str);
    }

    /**
     * Returns a configured view object
     *
     * @return Zend_View_Interface
     */
    protected function _getViewObject() {
        if (!Zend_Registry::isRegistered('application')) {
            throw new Exception('Application is not registered.');
        }

        $bootstrap = Zend_Registry::get('application')->getBootstrap();
        $view = $bootstrap->getResource('View');

        // Unfortunately specific conditional required when using the Ano_ZFTwig package.
        // This switches the rendering engine from twig to php, since the Spawn templates are still
        // in php.
        if ($view instanceof Ano_View) {
            $view->setTemplateEngine('php');
        }
        return $view;
    }

    protected function _shouldMinifyModels() {
        $config = Zend_Registry::get('config');
        return isset($config->spawn) && $config->spawn->minifyJsModels;
    }
}
