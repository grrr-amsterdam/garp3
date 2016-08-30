<?php
/**
 * G_View_Helper_HtmlTime
 * Render a <time> element
 *
 * @package G_View_Helper
 * @author  Harmen Janssen <harmen@grrr.nl>
 */

class G_View_Helper_HtmlTime extends Zend_View_Helper_HtmlElement {
    /**
     * Return an HTML <time> tag
     *
     * @param string $datetime Either a timestamp (checked for using is_numeric) or a date string
     * @param string $formatForHumans The display format, must be compatible with strftime
     * @param array $options Additional options
     * @return string
     */
    public function htmlTime($datetime, $formatForHumans, $options = array()) {
        $time = !is_numeric($datetime) ? strtotime($datetime) : $datetime;
        $this->_setDefaultOptions($options);
        $datetime = new Garp_DateTime('@' . $time);

        $attributes = array_merge(
            $options['attributes'],
            array(
                'datetime' => $datetime->format_local($options['formatForRobots'])
            )
        );

        $label = $datetime->format_local($formatForHumans);
        $html = '<time' . $this->_htmlAttribs($attributes) . '>' . $label . '</time>';
        return $html;
    }

    /**
     * Set default options
     *
     * @param array $options
     * @return void
     */
    protected function _setDefaultOptions(&$options) {
        $options = new Garp_Util_Configuration($options);
        $options->setDefault('formatForRobots', '%Y-%m-%d')
            ->setDefault('attributes', array());
        $options = (array)$options;
    }
}
