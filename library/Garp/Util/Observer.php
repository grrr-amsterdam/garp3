<?php
/**
 * Garp_Util_Observer
 * Interface for Observers.
 *
 * @package Garp_Util
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
interface Garp_Util_Observer {
    /**
     * It's handy for Observers to have a unique
     * identifier. Return that here.
     * By default the base classname will be returned (e.g. "Garp_Util_Observer" becomes "Observer")
     *
     * @return string
     */
    public function getName();

    /**
     * Receive events. This method looks for a method named after
     * the event (e.g. when the event is "beforeFetch", the method
     * executed will be "beforeFetch"). Subclasses may implement
     * this to act upon the event however they wish.
     *
     * @param string $event The name of the event
     * @param array $params Collection of parameters (contextual to the event)
     * @return void
     */
    public function receiveNotification($event, array $params = array());
}
