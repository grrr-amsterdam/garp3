<?php
/**
 * G_View_Helper_ImageTag
 * Generate Image tag (<img src="">)
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Helper
 * @lastmodified $Date: $
 */
class G_View_Helper_HtmlImage extends Zend_View_Helper_HtmlElement {
	/**
	 * Return an image <img> tag
	 * @param String $src The url to the image (e.g. <image src="$src">)
	 * @param Array $attributes More HTML attributes
	 * @return String
	 */
	public function htmlImage($src, array $attributes = array()) {
		$attributes['src'] = $src;
		$html = '<img'.$this->_htmlAttribs($attributes).'>';
		return $html;
	}
}