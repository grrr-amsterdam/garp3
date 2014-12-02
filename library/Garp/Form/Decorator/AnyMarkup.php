<?php
/**
 * Garp_Form_Decorator_AnyMarkup
 * Renders any markup. 
 * Taken from @link http://www.zfsnippets.com/snippets/view/id/62/anymarkup-decorator.
 * @author chiborg | http://www.zfsnippets.com/users/view/id/326?PHPSESSID=83d3765c54898331cfb9a4439f245cd3
 * @version 1
 * @package Garp
 * @subpackage Form
 */
class Garp_Form_Decorator_AnyMarkup extends Zend_Form_Decorator_Abstract {

    public function render($content) {
        $placement = $this->getPlacement();
        $separator = $this->getSeparator();
        switch ($placement) {
            case self::PREPEND:
                return $this->_options['markup'] . $separator . $content;
            case self::APPEND:
            default:
                return $content . $separator . $this->_options['markup'];
        }
    }

}
