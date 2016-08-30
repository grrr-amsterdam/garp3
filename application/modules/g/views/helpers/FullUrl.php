<?php
/**
 * G_View_Helper_FullUrl
 * Returns the full URL to a page within this website.
 *
 * @package G_View_Helper
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class G_View_Helper_FullUrl extends Zend_View_Helper_Abstract {
    /**
     * Create full URL from relative URL.
     *
     * @param string|bool $url Relative URL (will be passed thru baseUrl() if $omitBaseUrl = false)
     * @param bool $omitProtocol
     * @param bool $omitBaseUrl
     * @return string Full URL
     */
    public function fullUrl($url = false, $omitProtocol = false, $omitBaseUrl = false) {
        if (!$url) {
            $url = $this->view->url();
        }
        $fullUrl = new Garp_Util_FullUrl($url, $omitProtocol, $omitBaseUrl);
        return $fullUrl->__toString();
    }
}
