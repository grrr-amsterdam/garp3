<?php
use Garp\Functional as f;

/**
 * Garp_Form_Decorator_AnyMarkup
 * Renders any markup.
 *
 * Taken from @link http://www.zfsnippets.com/snippets/view/id/62/anymarkup-decorator.
 * By use chiborg, http://www.zfsnippets.com/users/view/id/326?PHPSESSID=83d3765c54898331cfb9a4439f245cd3*
 *
 * @package Garp_Form_Decorator
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Form_Decorator_AnyMarkup extends Zend_Form_Decorator_Abstract {

    public function render($content) {
        $placement = $this->getPlacement();
        $separator = $this->getSeparator();

        $html = $this->_options['markup'];
        $attribs = f\omit(['markup'], $this->_options);
        if (count($attribs)) {
            $html = $this->_addHtmlAttributes($html, $attribs);
        }

        if ($placement === self::PREPEND) {
            return $html . $separator . $content;
        }
        return $content . $separator . $html;
    }

    /**
     * Take the html string, turn into a DOM fragment, add the attributes, and convert back.
     * Bit experimental, requires being given valid and complete HTML (so no partial fragments).
     *
     * @param  string $html
     * @param  array  $attribs
     * @return string
     */
    protected function _addHtmlAttributes(string $html, array $attribs): string {
        $dom = new DOMDocument();
        $fragment = $dom->createDocumentFragment();
        $fragment->appendXML($html);
        $dom->appendChild($fragment);
        foreach ($attribs as $attrib => $value) {
            $dom->firstChild->setAttribute($attrib, $value);
        }
        return $dom->saveXML($dom->firstChild);
    }

}
