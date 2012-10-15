<?php
/**
 * G_View_Helper_Script
 * Various Javascript functions
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class G_View_Helper_Script extends Zend_View_Helper_Abstract {
	/**
	 * Collection of scripts
	 * @var Array
	 */
	protected static $_scripts = array();
	
	
	/**
	 * Central interface for this helper, used for chainability.
	 * Usage: $this->script()->render('...');
	 * @return $this
	 */
	public function script() {
		return $this;
	}
	
	
	/**
	 * Push a script to the stack. It will be rendered later.
	 * @param String $code
	 * @param Boolean $render Wether to render directly
	 * @return Mixed
	 */
	public function block($code, $render = false) {
		if ($render) {
			return $this->_renderScript($code);
		}
		static::$_scripts[] = array('type' => 'block', 'value' => $code);
		return $this;
	}
	
	
	/**
	 * Push a URL to a script to the stack. It will be rendered later.
	 * @param String $url
	 * @param Boolean $render Wether to render directly
	 * @return Mixed
	 */
	public function src($url, $render = false) {
		if ($render) {
			return $this->_renderSrc($url);
		}
		static::$_scripts[] = array('type' => 'src', 'value' => $url);
		return $this;
	}
	
		
	/**
	 * Render everything on the stack
	 * @return String
	 */
	public function render() {
		$string = '';
		foreach (static::$_scripts as $script) {
			if ($script['type'] === 'block') {
				$string .= $this->_renderScript($script['value']);
			} else {
				$string .= $this->_renderSrc($script['value']);
			}
		}
		return $string;
	}
	
	
	/**
	 * Render a Javascript.
	 * @param String $code If not given, everything in $this->_scripts will be rendered.
	 * @return String
	 */
	protected function _renderScript($code) {
		$html = "<script type=\"text/javascript\">\n\t%s\n</script>";
		return sprintf($html, $code);
	}
	
	
	/**
	 * Render Javascript tags with a "src" attribute.
	 * @param String $url If not given, everything in $this->_urls will be rendered.
	 * @return String
	 */
	protected function _renderSrc($url) {
		$html = '<script type="text/javascript" src="%s"></script>';
		if ('http://' !== substr($url, 0, 7) && 'https://' !== substr($url, 0, 8) && '//' !== substr($url, 0, 2)) {
			$url = $this->view->assetUrl($url);
		}
		return sprintf($html, $url);
	}
}
