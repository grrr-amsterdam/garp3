<?php
/**
 * Garp_Form_Element_File
 * 
 * ¡NOTE! This element is a lot dumber than Zend_Form_Element_File. It will not 
 * upload the file, it will not validate $_FILES. It is just a simple text input 
 * with type="file". This is by design, since we control what happens with files 
 * either in the controller or with an AJAX upload script controlled from 
 * garp.front.js.
 *
 * @author Harmen Janssen | grrr.nl
 * @version 1
 * @package Garp
 * @subpackage Form
 */
class Garp_Form_Element_File extends Zend_Form_Element_Xhtml {
    /**
     * @var string Default view helper
     */
    public $helper = 'formFile';
	
	
	public function init() {
		// parentClass might by set by Garp_Form
		$parentClass = null;
		if ($this->getDecorator('HtmlTag')) {
			$parentClass = $this->getDecorator('HtmlTag')->getOption('class');
		}

		$options = array(
			'decorators' => array(
				'ViewHelper',
				array(array('input-wrapper' => 'HtmlTag'), array('tag' => 'div', 'class' => 'file-input-wrapper')),
				'Label',
				'Description',
				'Errors',
				array(array('outer-wrapper' => 'HtmlTag'), array('tag' => 'div', 'class' => $parentClass))
			)
		);
		if (!$this->getDescription()) {
			$uploadInfoStr = $this->_getUploadInfoString();
			$options['description'] = $uploadInfoStr;
		}
		$this->setOptions($options);	

		// Render file metadata as data-attributes so Javascript can use them.
		if (!$this->getAttrib('data-max-file-size')) {
			$this->setAttrib('data-max-file-size', $this->getUploadMaxFilesize());
		}
		if (!$this->getAttrib('data-allowed-extensions')) {
			$extensions = implode(',', $this->getAllowedExtensions());
			$this->setAttrib('data-allowed-extensions', $extensions);		
		}
	}
	

	/**
 	 * Retrieve an informative string describing upload restrictions.
 	 * @return String
 	 */
	protected function _getUploadInfoString() {
		$maxFileSize = $this->getUploadMaxFilesize();
		$allowedExtensions = $this->getAllowedExtensions();
		$lastExtension = array_pop($allowedExtensions);

		$translator = $this->getTranslator();
		$uploadInfoStr = $translator->translate('Only %1$s and %2$s files with a maximum of %3$s MB are allowed');
		$uploadInfoStr = sprintf($uploadInfoStr, implode(', ', $allowedExtensions), $lastExtension, $maxFileSize); 
		
		return $uploadInfoStr;		
	}


	/**
 	 * Retrieve allowed extensions.
 	 * @return Array
 	 */
	public function getAllowedExtensions() {
		$file = $this->getFileObject();
		$allowedExtensions = $file->getAllowedExtensions();
		return $allowedExtensions;
	}


	/**
 	 * Retrieve max upload size
 	 * @return Int
 	 */
	public function getUploadMaxFilesize() {
		$file = $this->getFileObject();
		$maxFileSize = $file->getUploadMaxFilesize();
		return $maxFileSize;
	}


	/**
 	 * Get file object based on attribute data-type
 	 * @return Garp_File
 	 */
	public function getFileObject() {
		if ($this->getAttrib('data-type') === Garp_File::TYPE_IMAGES) {
			$file = new Garp_Image_File();
		} else {
			$file = new Garp_File();
		}
		return $file;
	}
}
