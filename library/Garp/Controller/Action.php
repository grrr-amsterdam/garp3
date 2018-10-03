<?php

/**
 * Garp_Controller_Action
 * Base controller class.
 *
 * @package Garp
 * @subpackage Controller
 * @author Harmen Janssen <harmen@grrr.nl>
 * @version $Revision: $
 * @modifiedby $LastChangedBy: $
 * @lastmodified $Date: $
 */
class Garp_Controller_Action extends Zend_Controller_Action {
    /**
     * Pre-dispatch routines
     *
     * Called before action method. If using class with
     * {@link Zend_Controller_Front}, it may modify the
     * {@link $_request Request object} and reset its dispatched flag in order
     * to skip processing the current action.
     *
     * @return void
     */
    public function preDispatch() {
        parent::preDispatch();
    }
}
