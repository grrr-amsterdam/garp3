<?php
/**
 * Mock model used by SluggableTest
 */
class Mocks_Model_SluggableTest extends Garp_Model_Db {
	protected $_name = '_sluggable_test';

	public function isMultilingual() {
		return false;
	}
}
