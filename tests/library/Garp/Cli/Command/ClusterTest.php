<?php
/**
 * @group Cli
 */
class Garp_Cli_Command_ClusterTest extends Garp_Test_PHPUnit_TestCase {
	protected $_testsEnabled = false;

	public function testScheduledJobsShouldBePickedUp() {
		if (!$this->_testsEnabled) {
			return;
		}
		$quiet = Garp_Cli::getQuiet();
		Garp_Cli::setQuiet(true);

		// Run cluster once, ot ensure check-in has happened
		$clusterCmd = new Garp_Cli_Command_Cluster();
		$clusterCmd->main(array('run'));

		Garp_Cache_Manager::scheduleClear(strtotime('now'), array());

		$clusterCmd->main(array('run'));
		Garp_Cli::setQuiet($quiet);

		$scheduledJobModel = new Model_ScheduledJob();
		$jobs = $scheduledJobModel->fetchAll();
		$this->assertEquals(1, count($jobs));
		$this->assertNotNull($jobs[0]->last_accepted_at);
		$this->assertNotNull($jobs[0]->accepter_id);
	}

	public function setUp() {
		$this->_testsEnabled = class_exists('Model_ScheduledJob');
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();

		if ($this->_testsEnabled) {
			$scheduledJobModel = new Model_ScheduledJob();
			$scheduledJobModel->delete('id > 0');
		}
	}
}
