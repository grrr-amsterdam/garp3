<?php
require_once 'Garp/3rdParty/htmlpurifier/library/HTMLPurifier.auto.php';

/**
 * Garp_3rdParty_HTMLPurifier_Filter_MyIframe
 * Custom filter for HTMLPurifier to allow iframes to be embedded in HTML.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Ext
 * @lastmodified $Date: $
 */
class Garp_3rdParty_Ext_HTMLPurifier_Filter_MyIframe extends HTMLPurifier_Filter {
	/**
	 * Name of the filter
	 * @var String
	 */
	public $name = 'MyIframe';


	/**
	 * Pre-processor function, handles HTML before HTML Purifier
	 */
	public function preFilter($html, $config, $context) {
		$regexp = '/<(\/?)iframe( ?)([^>]+)?>/i';
		$replace = '~$1iframe$2$3~';
		return preg_replace($regexp, $replace, $html);
	}


	/**
	 * Post-processor function, handles HTML after HTML Purifier
	 */
	public function postFilter($html, $config, $context) {
		$regexp = '/~(\/?)iframe( ?)([^~]+)?~/i';
		$replace = '<$1iframe$2$3>';
		return preg_replace($regexp, $replace, $html);
	}
}