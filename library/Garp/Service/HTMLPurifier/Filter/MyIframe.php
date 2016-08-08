<?php
/**
 * Garp_Service_HTMLPurifier_Filter_MyIframe
 * Custom filter for HTMLPurifier to allow iframes to be embedded in HTML.
 *
 * @package Garp_Service_HTMLPurifier_Filter
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Service_HTMLPurifier_Filter_MyIframe extends HTMLPurifier_Filter {
    /**
     * Name of the filter
     *
     * @var String
     */
    public $name = 'MyIframe';

    /**
     * Pre-processor function, handles HTML before HTML Purifier
     *
     * @param string $html
     * @param object $config
     * @param object $context
     * @return string
     */
    public function preFilter($html, $config, $context) {
        $regexp = '/<(\/?)iframe( ?)([^>]+)?>/i';
        $replace = '~$1iframe$2$3~';
        return preg_replace($regexp, $replace, $html);
    }


    /**
     * Post-processor function, handles HTML after HTML Purifier
     *
     * @param string $html
     * @param object $config
     * @param object $context
     * @return string
     */
    public function postFilter($html, $config, $context) {
        $regexp = '/~(\/?)iframe( ?)([^~]+)?~/i';
        $replace = '<$1iframe$2$3>';
        return preg_replace($regexp, $replace, $html);
    }
}
