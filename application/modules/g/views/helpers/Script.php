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
 	 * Render a script tag containing a minified script reference.
 	 * @param String $identifier Needs to be in the config under assets.js.$identifier
 	 * @param String $render Wether to render directly
 	 * @return String Script tag to the right file.
 	 * NOTE: this method does not check for the existence of said minified file.
 	 */
	public function minifiedSrc($identifier, $render = false) {
		$config = Zend_Registry::get('config');
		if (empty($config->assets->js->{$identifier})) {
			throw new Garp_Exception('JS configuration for identifier '.$identifier.' not found. '.
				'Please configure assets.js.'.$identifier);
		}
		$jsRoot = rtrim($config->assets->js->basePath ?: '/js', '/').'/';
		$config = $config->assets->js->{$identifier};
		if (!isset($config->disabled) || !$config->disabled) {
			// If minification is not disabled (for instance in a development environment),
			// return the path to the minified file.
			return $this->src($jsRoot.$config->filename, $render);
		} else {
			// Otherwise, return all the script tags for all the individual source files
			if (!isset($config->sourcefiles)) {
				return '';
			}
			$out = '';
			foreach ($config->sourcefiles as $sourceFile) {
				$response = $this->src($jsRoot.$sourceFile, $render);
				if ($render) {
					$out .= $response;
				}
			}
			if ($render) {
				return $out;
			}
			return $this;
		}
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
		$html = "<script>\n\t%s\n</script>";
		return sprintf($html, $code);
	}
	
	
	/**
	 * Render Javascript tags with a "src" attribute.
	 * @param String $url If not given, everything in $this->_urls will be rendered.
	 * @return String
	 */
	protected function _renderSrc($url) {
		$html = '<script src="%s"></script>';
		if ('http://' !== substr($url, 0, 7) && 'https://' !== substr($url, 0, 8) && '//' !== substr($url, 0, 2)) {
			$url = $this->view->assetUrl($url);
		}
		return sprintf($html, $url);
	}
}
