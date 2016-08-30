<?php
/**
 * ISN'T THIS THING INCREDIBLY OUTDATED AND DEPRECATED? (1-5-2012)
 * G_View_Helper_ImageTag
 * Generate Image tag (<img src="">)
 *
 * @package G_View_Helper
 * @author  David Spreekmeester <david@grrr.nl>
 */
class G_View_Helper_HtmlImage extends Zend_View_Helper_HtmlElement {
    /**
     * Return an image <img> tag
     *
     * @param string $src The url to the image (e.g. <image src="$src">)
     * @param array $attributes More HTML attributes
     * @return string
     */
    public function htmlImage($src, array $attributes = array()) {
        $attributes['src'] = $src;
        $html = '<img' . $this->_htmlAttribs($attributes) . '>';
        return $html;
    }
}
