<?php
class Mocks_StaticcacheController extends Garp_Controller_Action {
	public function init() {
		$this->_helper->cache(array('index', 'tagrequestwithprimarykeys'));
	}

	public function indexAction() {
		file_put_contents('/Users/harmen/Desktop/bla.txt', 'argh');
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
