<?php
class IndexController extends Garp_Controller_Action {
	public function indexAction() {
		$this->view->title = 'Home';
		$this->_helper->layout->setLayoutPath(GARP_APPLICATION_PATH.'/modules/g/views/layouts');
		$this->_helper->layout->setLayout('blank');		
	}
}
