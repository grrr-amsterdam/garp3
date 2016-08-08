<?php
/**
 * Garp_Service_HTMLPurifier_Filter_MyEmbed
 * Custom filter for HTMLPurifier to allow embeds to be embedded in HTML.
 *
 * @package Garp_Service_HTMLPurifier_Filter
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Service_HTMLPurifier_Filter_MyEmbed extends HTMLPurifier_Filter {
    /**
     * Name of the filter
     *
     * @var String
     */
    public $name = 'MyEmbed';

    /**
     * Pre-processor function, handles HTML before HTML Purifier
     *
     * @param string $html
     * @param object $config
     * @param object $context
     * @return string
     */
    public function preFilter($html, $config, $context) {
        $regexp = '/<(\/?)embed( ?)([^>]+)?>/i';
        $replace = '~$1embed$2$3~';
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
        $regexp = '/~(\/?)embed( ?)([^~]+)?~/i';
        $replace = '<$1embed$2$3>';
        return preg_replace($regexp, $replace, $html);
    }
}
