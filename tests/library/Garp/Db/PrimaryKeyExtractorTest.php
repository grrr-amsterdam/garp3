<?php
class Garp_Db_PrimaryKeyExtractorTest extends Garp_Test_PHPUnit_TestCase {
	public function setUp() {
		// Create bogus models
		$this->_singularPkModel = new Zend_Db_Table(array(
			'name' => 'singular_pk',
			'primary' => 'id'
		));
	}
}
