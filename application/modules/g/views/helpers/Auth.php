<?php
/**
 * G_View_Helper_Auth
 * class description
 *
 * @package G_View_Helper
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class G_View_Helper_Auth extends Zend_View_Helper_Abstract {
    /**
     * Method for chainability
     *
     * @return $this
     */
    public function auth() {
        return $this;
    }

    /**
     * Maps methods to Garp_Auth
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args) {
        $auth = Garp_Auth::getInstance();
        return call_user_func_array(array($auth, $method), $args);
    }
}
