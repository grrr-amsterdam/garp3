<?php
/**
 * Garp_Test_PHPUnit_ControllerTestCase
 * class description
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.4.0
 * @package      Garp_Test_PHPUnit
 */
ob_start();

abstract class Garp_Test_PHPUnit_ControllerTestCase extends Zend_Test_PHPUnit_ControllerTestCase {
	/**
 	 * Fixtures
 	 * @var Array
 	 */
	protected $_mockData = array();

    public $application;

	/** @var Garp_Test_PHPUnit_Helper */
	protected $_helper;

	public function __construct() {
		$this->_helper = new Garp_Test_PHPUnit_Helper();
		parent::__construct();
	}

	public function setUp() {
		/**
 	 	 * Very bummed out that the following line does not work. Somehow the application created in
 	 	 * tests/TestHelper.php is not the same as a new application _created in the exact same
 	 	 * way_.
 	 	 * It boggles the mind. The only difference is the non-working version is bootstrapped
 	 	 * inside TestHelper but if anything I'd think that would be an advantage.
 	 	 */
		//$this->application = Zend_Registry::get('application');
		$this->application = new Garp_Application(
			APPLICATION_ENV,
			APPLICATION_PATH.'/configs/application.ini'
		);
		var_dump($this->application); exit;

		$this->bootstrap = array($this, 'appBootstrap');

		$this->_helper->setUp($this->_mockData);
		parent::setUp();
	}

	public function tearDown() {
		$this->_helper->tearDown($this->_mockData);
		parent::tearDown();
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

	/**
 	 * Convenience method for checking if a given route exists
 	 */
	public function assertRouteIsAlive($controller, $action, $module = 'default') {
	    $url = $this->url($this->urlizeOptions(array(
			'controller' => $controller,
			'action' 	 => $action,
			'module' 	 => $module
		)));
	    $this->dispatch($url);

	    $this->assertController($controller);
		$this->assertAction($action);
	    $this->assertModule($module);
	}

	public function getDatabaseAdapter() {
		$dbAdapter = $this->getFrontController()
			->getParam('bootstrap')
			->getResource('db')
		;
		return $dbAdapter;
	}

    public function appBootstrap() {
        $this->application->bootstrap();
		Zend_Controller_Front::getInstance()->setParam('bootstrap', $this->application->getBootstrap());
		Zend_Registry::set('application', $this->application);
	}

}
