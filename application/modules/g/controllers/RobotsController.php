<?php
/**
 * G_RobotsController
 * Returns a robots.txt
 *
 * @package G_Controllers
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class G_RobotsController extends Garp_Controller_Action {
    /**
     * Renders a robots.txt
     *
     * @return void
     */
    public function indexAction() {
        if ($this->_helper->hasHelper('layout')) {
            $this->_helper->layout->disableLayout();
        }
        $this->getResponse()->setHeader('Content-Type', 'text/plain');
    }
}
