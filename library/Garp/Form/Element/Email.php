<?php

/**
 * Garp_Form_Element_Email
 *
 * @package Garp
 * @author Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Form_Element_Email extends Garp_Form_Element_Text {

    public function init() {
        $this->addFilter('PregReplace', array('match' => '/\s+/', 'replace' => ''));
        $this->addValidator('EmailAddress', false);
    }

}
