<?php
/**
 * Garp_Form_Element_Radio
 * class description
 *
 * @package Garp_Form_Element
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Form_Element_Radio extends Zend_Form_Element_Radio {

    /**
     * Wether to auto-select the first radio
     *
     * @var bool
     */
    protected $_autoSelectFirstValue = true;

    public function init() {
        $htmlLegendClass = 'multi-input-legend';
        if ($this->isRequired()) {
            $htmlLegendClass .= ' required';
        }
        $labelText = $this->getLabel();
        if ($this->getDecorator('Label')->getRequiredSuffix() && $this->isRequired()) {
            $labelText .= $this->getDecorator('Label')->getRequiredSuffix();
        }
        $legendHtml = "<p class=\"{$htmlLegendClass}\">{$labelText}</p>";

        $ulClass = 'multi-input';
        if ($this->isrequired()) {
            $ulClass .= ' required';
        }
        if ($defaulthtmltagrenderer = $this->getdecorator('htmltag')) {
            $parentclass = $defaulthtmltagrenderer->getoption('class');
            $ulClass .= ' ' . $parentclass;
        }

        $this->setOptions(
            array(
                'separator' => '</li><li>',
                'decorators' => array(
                    'ViewHelper',
                    'Description',
                    array(
                        array('tag1' => 'HtmlTag'),
                        array('tag' => 'li')
                    ),
                    array(
                        array('tag2' => 'HtmlTag'),
                        array('tag' => 'ul', 'class' => $ulClass)
                    ),
                    array(
                        'AnyMarkup',
                        array('markup' => $legendHtml, 'placement' => 'prepend')
                    ),
                    'Errors',
                    array(
                        array('tag3' => 'HtmlTag'),
                        array('tag' => 'div', 'class' => 'multi-input-container')
                    ),
                )
            )
        );

        if ($this->_autoSelectFirstValue) {
            $optionKeys = array_keys($this->options);
            $this->setValue($optionKeys[0]);
        }
    }

    /**
     * Set autoSelectFirstValue
     *
     * @param bool $autoSelectFirstValue
     * @return $this
     */
    public function setAutoSelectFirstValue($autoSelectFirstValue) {
        $this->_autoSelectFirstValue = $autoSelectFirstValue;
        return $this;
    }

}
