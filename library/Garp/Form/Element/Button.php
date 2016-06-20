<?php
/**
 * Garp_Form_Element_Button
 * Overwritten to remove label decorator
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      Garp_Form_Element
 */
class Garp_Form_Element_Button extends Zend_Form_Element_Button {

        public function init() {
            // Don't be silly. Buttons don't use labels.
            $this->removeDecorator('Label');
        }

}
