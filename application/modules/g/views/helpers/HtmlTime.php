<?php
/**
 * G_View_Helper_HtmlTime
 * Render a <time> element
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      G_View_Helper
 */
  
class G_View_Helper_HtmlTime extends Zend_View_Helper_HtmlElement {
	/**
	 * Return an HTML <time> tag
	 * @param String $datetime Either a timestamp (checked for using is_numeric) or a date string
	 * @param String $formatForHumans The display format, must be compatible with strftime
	 * @param Array $options Additional options
	 * @return String
	 */
	public function htmlTime($datetime, $formatForHumans, $options = array()) {
		$time = !is_numeric($datetime) ? strtotime($datetime) : $datetime;
		$this->_setDefaultOptions($options);
		$attributes = array_merge($options['attributes'], array(
			'datetime' => strftime($options['formatForRobots'], $time)
		));
		$label = strftime($formatForHumans, $time);
		$html = '<time'.$this->_htmlAttribs($attributes).'>'.$label.'</time>';
		return $html;
	}

	/**
 	 * Set default options
 	 * @return Void
 	 */
	protected function _setDefaultOptions(&$options) {
		$options = new Garp_Util_Configuration($options);
		$options
			->setDefault('formatForRobots', '%Y-%m-%d')
			->setDefault('attributes', array())
		;
		$options = (array)$options;
	}
}
