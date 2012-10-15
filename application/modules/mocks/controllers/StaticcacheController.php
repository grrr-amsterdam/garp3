<?php
class Mocks_StaticcacheController extends Garp_Controller_Action {
	public function init() {
		$this->_helper->cache(array('index', 'tagrequestwithprimarykeys'));
	}

	public function indexAction() {
	}

	public function primarykeystoreAction() {
		$modelThing = new Mocks_Model_CMThing();
		$modelThing->fetchAll();
	}

	public function tagrequestwithprimarykeysAction() {
		$modelThing = new Mocks_Model_CMThing();
		$modelThing->fetchAll();
	}
}
