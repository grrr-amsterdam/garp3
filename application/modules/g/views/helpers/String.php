<?php
/**
 * G_View_Helper_String
 * Various String helper functionality.
 *
 * @package G_View_Helper
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class G_View_Helper_String extends Zend_View_Helper_Abstract {
    /**
     * Chain method.
     *
     * @return G_View_Helper_String
     */
    public function string() {
        return $this;
    }

    /**
     * Maps methods to Garp_Util_String
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args) {
        return call_user_func_array(array('Garp_Util_String', $method), $args);
    }
}
