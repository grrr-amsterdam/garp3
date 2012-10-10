<?php
require_once 'Garp/3rdParty/htmlpurifier/library/HTMLPurifier.auto.php';

/**
 * Garp_3rdParty_HTMLPurifier_Filter_MyEmbed
 * Custom filter for HTMLPurifier to allow embeds to be embedded in HTML.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Ext
 * @lastmodified $Date: $
 */
class Garp_3rdParty_Ext_HTMLPurifier_Filter_MyEmbed extends HTMLPurifier_Filter {
	/**
	 * Name of the filter
	 * @var String
	 */
	public $name = 'MyEmbed';


	/**
	 * Pre-processor function, handles HTML before HTML Purifier
	 */
	public function preFilter($html, $config, $context) {
		$regexp = '/<(\/?)embed( ?)([^>]+)?>/i';
		$replace = '~$1embed$2$3~';
		return preg_replace($regexp, $replace, $html);
	}


	/**
	 * Post-processor function, handles HTML after HTML Purifier
	 */
	public function postFilter($html, $config, $context) {
		$regexp = '/~(\/?)embed( ?)([^~]+)?~/i';
		$replace = '<$1embed$2$3>';
		return preg_replace($regexp, $replace, $html);
	}
}
