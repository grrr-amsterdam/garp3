<?php
class IndexController extends Garp_Controller_Action {
	public function indexAction() {
		$this->view->title = 'Home';
	}
}