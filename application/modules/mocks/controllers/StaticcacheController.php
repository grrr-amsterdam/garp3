<?php
class Mocks_StaticcacheController extends Garp_Controller_Action {
	public function init() {
		$this->_helper->cache(array('index', 'tagrequestwithprimarykeys'));
		$this->_helper->layout->setLayoutPath(GARP_APPLICATION_PATH.'/modules/g/views/layouts');
		$this->_helper->layout->setLayout('blank');
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
