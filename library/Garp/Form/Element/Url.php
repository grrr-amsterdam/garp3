<?php
/**
 * Garp_Form_Element_Url
 *
 * @package Garp_Form_Element
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Form_Element_Url extends Garp_Form_Element_Text {

    public function init() {
        parent::init();

        $this->addFilter(new Garp_Filter_ForceUriScheme());
        $this->addValidator(new Garp_Validate_Url());
    }

}
