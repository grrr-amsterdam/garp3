<?php
/**
 * G_View_Helper_HtmlLink
 * Generate HTML links (<a href="#">...</a>)
 *
 * @package G_View_Helper
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class G_View_Helper_HtmlLink extends Zend_View_Helper_HtmlElement {
    /**
     * Return a HTML <a> tag
     *
     * @param array|string $url        The url to link to (e.g. <a href="$url">),
     *                                 or an array with values matching Zend_View_Helper_Url::url().
     * @param string       $label      The link label (e.g. <a href="$url">$label</a>)
     * @param array        $attributes More attributes
     * @param bool         $escape     Wether to escape attributes and label
     * @return string
     */
    public function htmlLink($url, $label, array $attributes = array(), $escape = true) {
        if (is_array($url)) {
            $url = call_user_func_array(array($this->view, 'url'), $url);
        } elseif ($url instanceof Garp_Util_RoutedUrl) {
            $url = (string)$url;
        } else {
            $urlAttribs = parse_url($url);
            if (empty($urlAttribs['scheme']) && substr($url, 0, 2) !== '//') {
                $url = $this->view->baseUrl($url);
            }
        }
        $attributes['href'] = $url;
        $label = $escape ? $this->view->escape($label) : $label;
        $html = '<a' . $this->_htmlAttribs($attributes) . '>' . $label . '</a>';
        return $html;
    }
}
