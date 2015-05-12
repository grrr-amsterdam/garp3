<?php
/**
 * G_View_Helper_Breadcrumbs
 * Prints breadcrumbs.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      G_View_Helper
 */
class G_View_Helper_Breadcrumbs extends Zend_View_Helper_Abstract {

	/**
 	 * @todo Make a proper thing out of this, with configurable output. (html, classes etc.)
 	 */
	public function breadcrumbs(array $links) {

		$out = '<div class="breadcrumbs">'."\n".'<ol>'."\n";
		foreach ($links as $url => $label) {
			$out .= "\t<li>{$this->view->htmlLink($url, $label)}</li>\n";
		}
		$out .= "</ol>\n</div>";
		return $out;
	}
}
