<?php
/**
 * @package Garp_Form_Element
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Form_Element_MultiCheckbox extends Zend_Form_Element_MultiCheckbox {

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
        if ($this->isRequired()) {
            $ulClass .= ' required';
        }
        if ($defaultHtmlTagRenderer = $this->getDecorator('htmlTag')) {
            $parentClass = $defaultHtmlTagRenderer->getOption('class');
            if (is_array($parentClass) && array_key_exists('callback', $parentClass)) {
                $ulClass = [
                    'callback' => function ($decorator) use ($parentClass, $ulClass) {
                        return $ulClass . ' ' . $parentClass['callback']($decorator);
                    }
                ];
            } else {
                $ulClass .= ' ' . $parentClass;
            }
        }

        $this->setOptions(
            [
                'separator' => '</li><li>',
                'decorators' => [
                    'ViewHelper',
                    'Description',
                    [
                        'Wrapper' => ['tag1' => 'HtmlTag'],
                        ['tag' => 'li']
                    ],
                    [
                        ['tag2' => 'HtmlTag'],
                        ['tag' => 'ul', 'class' => $ulClass]
                    ],
                    [
                        'AnyMarkup',
                        ['markup' => $legendHtml, 'placement' => 'prepend']
                    ],
                    'Errors',
                    [
                        ['tag3' => 'HtmlTag'],
                        ['tag' => 'div', 'class' => 'multi-input-container']
                    ],
                ]
            ]
        );
    }

}

