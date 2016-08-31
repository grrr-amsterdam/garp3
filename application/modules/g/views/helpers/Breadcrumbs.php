<?php
/**
 * G_View_Helper_Breadcrumbs
 * Prints breadcrumbs.
 *
 * @package G_View_Helper
 * @author  Mattijs Bliek <mattijs@grrr.nl>
 */
class G_View_Helper_Breadcrumbs extends Zend_View_Helper_Abstract {

    /**
     * Render breadcrumbs
     *
     * @param array $links
     * @return string
     * @todo Make a proper thing out of this, with configurable output. (html, classes etc.)
     */
    public function breadcrumbs(array $links) {

        $out = '<div class="breadcrumbs">' . "\n" . '<ol>' . "\n";
        foreach ($links as $url => $label) {
            $out .= "\t<li>{$this->view->htmlLink($url, $label)}</li>\n";
        }
        $out .= "</ol>\n</div>";
        return $out;
    }
}
