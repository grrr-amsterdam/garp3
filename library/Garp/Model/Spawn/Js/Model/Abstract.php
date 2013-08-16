<?php
/**
 * Generated JS model
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Model
 */
abstract class Garp_Model_Spawn_Js_Model_Abstract {
	protected $_modelId;
	protected $_modelSet;


	public function __construct($modelId, Garp_Model_Spawn_ModelSet $modelSet) {
		$this->_modelId = $modelId;
		$this->_modelSet = $modelSet;
	}
	
	
	public function render() {
		if (!Zend_Registry::isRegistered('application')) {
			throw new Exception('Application is not registered.');
		}

		$bootstrap = Zend_Registry::get('application')->getBootstrap();
		$view = $bootstrap->getResource('View');		

		$view->model = $this->_modelSet[$this->_modelId];
		$view->modelSet = $this->_modelSet;

		$view->setScriptPath(GARP_APPLICATION_PATH.'/modules/g/views/scripts/spawn/js/');
		return $view->render($this->_template);
	}
	
	
	protected function _beautify($str) {
		require_once(APPLICATION_PATH.'/../library/Garp/3rdParty/JsBeautifier/jsbeautifier.php');
		return js_beautify($str);
	}
	
	
	protected function _minify($str) {
		require_once(APPLICATION_PATH . "/../library/Garp/3rdParty/minify/lib/JSMin.php");
		return JSMin::minify($str);
	}
}
