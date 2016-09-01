<?php
/**
 * G_View_Helper_AssetUrl
 * Generate URLs for assets (CSS, Javascript, Images, Flash)
 *
 * @package G_View_Helper
 * @author  Harmen Janssen <harmen@grrr.nl>
 * @author  David Spreekmeester <david@grrr.nl>
 */
class G_View_Helper_AssetUrl extends Zend_View_Helper_Abstract {
    public function assetUrl($file = null, $forced_extension = false) {
        if (!func_num_args()) {
            return $this;
        }
        return new Garp_Util_AssetUrl($file, $forced_extension);
    }

    public function __call($method, array $args) {
        $assetUrl = new Garp_Util_AssetUrl();
        return call_user_func_array(array($assetUrl, $method), $args);
    }
}
