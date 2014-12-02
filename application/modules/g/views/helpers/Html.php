<?php
/**
 * G_View_Helper_Html
 * Renders any HTML
 * @author Harmen Janssen | grrr.nl
 * @version 1
 * @package Garp
 * @subpackage Helper
 *
 * @todo Allow self-closing tags
 */
class G_View_Helper_Html extends Zend_View_Helper_HtmlElement {
	
	/**
 	 * Render the HTML.
 	 * @param String $tag
 	 * @param Array $attributes
 	 * @param String $value
 	 * @return String
 	 */
	public function html($tag, $value = null, array $attributes = array()) {
		// This happens when used from a Garp_Form context
		if (array_key_exists('id', $attributes) && !$attributes['id']) {
			unset($attributes['id']);
		}
		if (isset($attributes['tag'])) {
			$tag = $attributes['tag'];
			unset($attributes['tag']);
		}
		$escape = true;
		if (array_key_exists('escape', $attributes)) {
			$escape = $attributes['escape'];
			unset($attributes['escape']);
		}

		$html  = '<'.$tag;
		$html .= $this->_htmlAttribs($attributes).'>';
		if ($value) {
			if ($escape) {
				$value = $this->view->escape($value);
			}
			$html .= $value;
		}
		$html .= '</'.$tag.'>';
		return $html;
	}

}
