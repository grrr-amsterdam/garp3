<?php
/**
*
* @group Helpers
*/

class G_View_Helper_PartialTest extends Garp_Test_PHPUnit_TestCase {

	public function testShouldHaveGarpHelperByDefault() {
		$garpPartial = $this->_getPartialHelper();
		$this->assertEquals( get_class($garpPartial), "G_View_Helper_Partial");
	}

	public function testGarpOutputMethodShouldBeEqualToZendMethod() {
		$this->assertEquals( $this->_createOneGarpView(), $this->_createOneZendView() );
	}

	public function testShouldNotLeaveGlobalVariables() {
		$view = $this->_getView();
		
		$garpPartial = $this->_getPartialHelper();
		$outputGarp = $garpPartial->partial('partials/excerpt.phtml', 'mocks', array(
			'username' => 'Johnny'
		));
		$this->assertNull($view->username);
	}

	public function testShouldNotOverrideViewGlobals() {
		$view = $this->_getView();
		$view->username = 'Michael';

		$garpPartial = $this->_getPartialHelper();
		$outputGarp = $garpPartial->partial('partials/excerpt.phtml', 'mocks', array(
			'username' => 'Johnny'
		));
		$this->assertEquals($view->username, 'Michael');
	}

	public function testShouldWorkWithNullModel() {
		$garpPartial = $this->_getPartialHelper();
		$outputGarp = $garpPartial->partial('partials/excerpt.phtml', 'mocks' );

		$zendPartial = new Zend_View_Helper_Partial();
		$zendPartial->setView(Zend_Registry::get('application')->getBootstrap()->getResource('View'));
		$outputZend = $zendPartial->partial('partials/excerpt.phtml', 'mocks');

		$this->assertEquals($outputGarp, $outputZend);
	}

	protected function setUp(){
		require_once 'garp/application/modules/mocks/resources/PartialTestMockDataGenerator.php';
		$generator = new PartialTestMockDataGenerator();
		$this->_staticArgs = $generator->wordpressDatabaseArray();
	}

	protected $_staticArgs;
	const NUMBER_OF_VIEWS_TO_GENERATE = 999;

 	/**
	 * Benchmarks the performance of the garpPartial compared to the zendPartial
     * uses the methods _createALotOfGarpViews() and _createAlotOfZendViews()
     * uses a for loop to generate garp views and zend views alternatively
     * 
     * the output on the screen is the time needed to generate the zend views - the time neded to generate the garp views => a positive number means that the garp tweak is better.
     */
	protected function benchmark() {
 
		//for some reason the order here matters,
		// so usually when zend views are generated first, the first batch seems to have a better performance than the garp views
		// if the order is switched, the garp views take less time allways

		for ($iterations=0; $iterations<10; $iterations++){
			$zendTimeTotal = $this->_createALotOfZendViews();
			$garpTimeTotal = $this->_createALotOfGarpViews();
			$deltaPerformance = $zendTimeTotal - $garpTimeTotal;
			
			$garpIsFaster = $deltaPerformance > 0;
            $outputColor = $garpIsFaster ? Garp_Cli::GREEN : Garp_Cli::RED;
            $msg = $garpIsFaster ? '                Garp is faster!' : 'Zend is faster';
            Garp_Cli::lineOut('Zend first:', Garp_Cli::YELLOW);
            Garp_Cli::lineOut($msg, $outputColor);


			$garpTimeTotal = $this->_createALotOfGarpViews();
			$zendTimeTotal = $this->_createALotOfZendViews();
			$deltaPerformance = $zendTimeTotal - $garpTimeTotal;
			
			 $garpIsFaster = $deltaPerformance > 0;
            $outputColor = $garpIsFaster ? Garp_Cli::GREEN : Garp_Cli::RED;
            $msg = $garpIsFaster ? '                Garp is faster!' : 'Zend is faster';
            Garp_Cli::lineOut('Garp first:', Garp_Cli::YELLOW);
            Garp_Cli::lineOut($msg, $outputColor);
		}
	}

	protected function _createOneGarpView() {
		$garpPartial = $this->_getPartialHelper();
		return call_user_func_array(array($garpPartial, 'partial'), $this->_staticArgs);
	}

	protected function _createOneZendView() {
		$zendPartial = new Zend_View_Helper_Partial();
		$zendPartial->setView(Zend_Registry::get('application')->getBootstrap()->getResource('View'));
		return call_user_func_array(array($zendPartial, 'partial'), $this->_staticArgs);
	}

	protected function _createALotOfGarpViews() {
		$garpTimeStart = microtime(true);
		for ($i = 0; $i < self::NUMBER_OF_VIEWS_TO_GENERATE; $i++) {
			$this->_createOneGarpView();
		}
		return microtime(true) - $garpTimeStart;
	}

	protected function _createALotOfZendViews() {
		$zendTimeStart = microtime(true);
		for ($i = 0; $i < self::NUMBER_OF_VIEWS_TO_GENERATE; $i++) {
			$this->_createOneZendView();
		}
		return microtime(true) - $zendTimeStart;
	}

	protected function _getPartialHelper() {
		return $this->_getView()->getHelper('partial');
	}

	protected function _getView() {
		return Zend_Registry::get('application')->getBootstrap()->getResource('View');
	}

	protected function tearDown() {
		$this->_getView()->clearVars();
	}

}