<?php
ob_start();

abstract class Garp_Test_PHPUnit_ControllerTestCase extends Zend_Test_PHPUnit_ControllerTestCase {
    public $application;

	/** @var Garp_Test_PHPUnit_Helper */
	protected $_helper;

	public function __construct() {
		$this->_helper = new Garp_Test_PHPUnit_Helper();
		parent::__construct();
	}

	/**
 	 * Overwritten to toss exceptions in your face, so developers don't have to inspect the 
 	 * HTML response to see what went wrong.
 	 * We'll skip Zend_Controller_Action_Exceptions though, because they actually provide semantic 
 	 * meaning to response. For instance, you can use them to adjust the HTTP status code, which is 
 	 * actually a valid response to these exception.
 	 * This method should throw only unexpected exceptions that need fixing right away.
 	 */
    public function dispatch($url = null) {
		$response = parent::dispatch($url);
		if (!$this->getResponse()->isException()) {
			return $response;
		}
		foreach ($this->getResponse()->getException() as $exp) {
			if (!$exp instanceof Zend_Controller_Action_Exception) {
				throw $exp;
			}
		}
		return $response;
	}
	
    public function setUp() {
		$this->application = new Garp_Application(
			APPLICATION_ENV,
			APPLICATION_PATH.'/configs/application.ini'
		);

		$this->bootstrap = array($this, 'appBootstrap');
		parent::setUp();
	}


    public function appBootstrap() {
        $this->application->bootstrap();
		Zend_Controller_Front::getInstance()->setParam('bootstrap', $this->application->getBootstrap());
		Zend_Registry::set('application', $this->application);
	}
	
	
	public function assertRouteIsAlive($controller, $action, $module = 'default') {
		$params = array(
			'controller' => $controller,
			'action' 	 => $action,
			'module' 	 => $module
		);

	    $url = $this->url($this->urlizeOptions($params));
	    $this->dispatch($url);

	    $this->assertController($params['controller']);
		$this->assertAction($params['action']);
	    $this->assertModule($params['module']);
	}


	public function getDatabaseAdapter() {
		$dbAdapter = $this->getFrontController()
			->getParam('bootstrap')
			->getResource('db')
		;
		return $dbAdapter;
	}
}
