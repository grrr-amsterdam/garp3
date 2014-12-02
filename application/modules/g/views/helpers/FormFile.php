<?php
/**
 * G_View_Helper_FormFile
 * Renders a file input. I am displeased with Zend_View_Helper_FormFile because 
 * the API differs from the other form-helpers and as such it is not usable with 
 * the ViewHelper decorator.
 * This helper fixes that in that it acts as an adapter from 
 * Zend_Form_Decorator_ViewHelper to Zend_View_Helper_FormFile
 * @author Harmen Janssen | grrr.nl
 * @version 1
 * @package Garp
 * @subpackage Helper
 */
class G_View_Helper_FormFile extends Zend_View_Helper_FormElement {

	/**
 	 * @param String $name
 	 * @param String $value Disregarded in this case: only there to match API
 	 * @param Array $attribs
 	 */
	public function formFile($name, $value, $attribs) {
		// taken from Zend_View_Helper_FormFile:
		$info = $this->_getInfo($name, null, $attribs);
        extract($info); // name, id, value, attribs, options, listsep, disable

        // is it disabled?
        $disabled = '';
        if ($disable) {
            $disabled = ' disabled="disabled"';
        }

        // XHTML or HTML end tag?
        $endTag = ' />';
        if (($this->view instanceof Zend_View_Abstract) && !$this->view->doctype()->isXhtml()) {
            $endTag= '>';
        }

        // build the element
		if (!empty($attribs['class']) && $attribs['class'] == 'hijack-upload') {
			$xhtml = '<noscript data-name="'.$this->view->escape($name).'" id="'.$this->view->escape($id).'" '.
				$this->_htmlAttribs($attribs).'><p><em>Om bestanden te uploaden dient Javascript ingeschakeld te zijn.'.
				'</em></p></noscript>'
			;
		} else {
        	$xhtml = '<input type="file"'
                	. ' name="' . $this->view->escape($name) . '"'
                	. ' id="' . $this->view->escape($id) . '"'
                	. $disabled
                	. $this->_htmlAttribs($attribs)
                	. $endTag;
		}
        return $xhtml;
	}

}
