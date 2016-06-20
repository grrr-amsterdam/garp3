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
class G_View_Helper_Script extends Zend_View_Helper_HtmlElement {
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
            throw new Garp_Exception("JS configuration for identifier {$identifier} not found. ".
                "Please configure assets.js.{$identifier}");
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
     * @param Array $attrs HTML attributes
     * @return Mixed
     */
    public function block($code, $render = false, array $attrs = array()) {
        return $this->_storeOrRender('block', array(
            'value' => $code, 'render' => $render, 'attrs' => $attrs
        ));
    }

    /**
     * Push a URL to a script to the stack. It will be rendered later.
     * @param String $url
     * @param Boolean $render Wether to render directly
     * @param Array $attrs HTML attributes
     * @return Mixed
     */
    public function src($url, $render = false, array $attrs = array()) {
        return $this->_storeOrRender('src', array(
            'value' => $url, 'render' => $render, 'attrs' => $attrs
        ));
    }

    /**
     * Render everything on the stack
     * @return String
     */
    public function render() {
        $string = '';
        foreach (static::$_scripts as $script) {
            $method = '_render' . ucfirst($script['type']);
            $string .= $this->{$method}($script['value'], $script['attrs']);
        }
        return $string;
    }

    /**
     * Save a script (src or block) to the stack, or render immediately
     */
    protected function _storeOrRender($type, array $args) {
        if ($args['render']) {
            $method = '_render' . ucfirst($type);
            return $this->{$method}($args['value'], $args['attrs']);
        }
        static::$_scripts[] = array(
            'type'  => $type,
            'value' => $args['value'],
            'attrs' => $args['attrs']
        );
        return $this;
    }

    /**
     * Render a Javascript.
     * @param String $code If not given, everything in $this->_scripts will be rendered.
     * @return String
     */
    protected function _renderBlock($code, array $attrs = array()) {
        $attrs = $this->_htmlAttribs($attrs);
        $html = "<script{$attrs}>\n\t%s\n</script>";
        return sprintf($html, $code);
    }

    /**
     * Render Javascript tags with a "src" attribute.
     * @param String $url If not given, everything in $this->_urls will be rendered.
     * @return String
     */
    protected function _renderSrc($url, array $attrs = array()) {
        if ('http://' !== substr($url, 0, 7) && 'https://' !== substr($url, 0, 8) && '//' !== substr($url, 0, 2)) {
            $url = $this->view->assetUrl($url);
        }
        $attrs['src'] = $url;
        $attrs = $this->_htmlAttribs($attrs);
        return "<script{$attrs}></script>";
    }
}
