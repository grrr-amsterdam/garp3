<?php
ob_start();

abstract class Garp_Test_PHPUnit_ControllerTestCase extends Zend_Test_PHPUnit_ControllerTestCase {
    public $application;
	
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
