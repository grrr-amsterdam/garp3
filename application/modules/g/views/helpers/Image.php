<?php
/**
 * G_View_Helper_Image
 * Assists in rendering dynamic images.
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Helper
 * @lastmodified $Date: $
 */
class G_View_Helper_Image extends Zend_View_Helper_HtmlElement {
	/**
 	 * Store configuration, don't fetch it fresh every time.
 	 * @var Zend_Config_Ini
 	 */
	protected $_config;


	/**
 	 * @var Garp_Image_Scaler
 	 */
	protected $_scaler;


	/**
	 * Instance conveyer method to enable calling of the other methods in this class from the view.
	 */
	public function image() {
		return $this;
	}


	public function __call($method, $args) {
		if ($method === 'render') {
			//	if $image is a filename, call _renderStatic(), otherwise call _renderUpload().
			if ($this->_isFilename($args[0])) {
				return call_user_func_array(array($this, '_renderStatic'), $args);
			} else {
				return call_user_func_array(array($this, '_renderUpload'), $args);
			}
		}
	}


	public function getUrl($image, $template = null) {
		if ($this->_isFilename($image)) {
			$file = new Garp_Image_File('static');
			return $file->getUrl($image);
		} else {
			if ($template) {
				return $this->_getImageScaler()->getScaledUrl($image, $template);
			} else {
				$file = new Garp_Image_File();
				return $file->getUrl($image);
			}
		}
	}


	protected function _isFilename($image) {
		return 
			is_string($image) &&
			strpos($image, '.') !== false
		;
	}


	protected function _renderStatic($filename, Array $htmlAttribs = array()) {
		$file = new Garp_Image_File('static');
		$src = $file->getUrl($filename);

		if (!array_key_exists('alt', $htmlAttribs)) {
			$htmlAttribs['alt'] = '';
		}

		return $this->view->htmlImage($src, $htmlAttribs);
	}


	/**
	 * Returns an HTML image tag, with the correct path to the image provided.
	 * @param Mixed $image						Id of the image record, or a Garp_Db_Table_Row image record. 
	 * 											This can also be an instance of an Image model. If so, the image will
	 * 											be rendered inside a partial that includes its caption and other metadata.
	 * @param Array $template					Template name.
	 * @param Array $htmlAttribs				HTML attributes for this <img> tag, such as 'alt'.
	 * @param String $partial 					Custom partial for rendering this image
	 * @return String							Full image tag string, containing attributes and full path
	 */
	protected function _renderUpload($imageIdOrRecord, $template = null, Array $htmlAttribs = array(), $partial = '') {
		if (!empty($template)) {
			$scaler = $this->_getImageScaler();
			$src = $scaler->getScaledUrl($imageIdOrRecord, $template);
			$tplScalingParams = $scaler->getTemplateParameters($template);
			$this->_addSizeParamsToHtmlAttribs($tplScalingParams, $htmlAttribs);
		} else {
			if ($imageIdOrRecord instanceof Garp_Db_Table_Row) {
				$filename = $imageIdOrRecord->filename;
			} else {
				$imageModel = new G_Model_Image();
				$filename = $imageModel->fetchFilenameById($imageIdOrRecord);
			}
			$file = new Garp_Image_File('upload');
			$src = $file->getUrl($filename);
		}

		if (!array_key_exists('alt', $htmlAttribs)) {
			$htmlAttribs['alt'] = '';
		}

		$htmlAttribs['src'] = $src;
		$imgTag = '<img'.$this->_htmlAttribs($htmlAttribs).'>';
		
		if ($imageIdOrRecord instanceof Garp_Db_Table_Row) {
			if ($partial) {
				$module  = 'default';
			} else {
				$partial = 'partials/image.phtml';
				$module  = 'g';
			}
			return $this->view->partial($partial, $module, array(
				'imgTag' => $imgTag,
				'imgObject' => $imageIdOrRecord
			));
		} else {
			return $imgTag;
		}
	}


	/**
	 * If available, adds 'width' and 'height' tags to the provided html attributes array, by reference.
	 * @param Array $scalingParams Scaling parameters, either custom or distilled from template configuration
	 * @return Void
	 */
	private function _addSizeParamsToHtmlAttribs($scalingParams, &$htmlAttribs) {
		if (
			array_key_exists('w', $scalingParams) &&
			$scalingParams['w']
		) {
			$htmlAttribs['width'] = $scalingParams['w'];
		}

		if (!$this->_config) {
			$this->_config = Garp_Cache_Ini::factory(APPLICATION_PATH.'/configs/application.ini');
		}

		if (
			is_null($this->_config->image->setHtmlHeight) ||
			$this->_config->image->setHtmlHeight
		) {
			if (
				array_key_exists('h', $scalingParams) &&
				$scalingParams['h']
			) {
				$htmlAttribs['height'] = $scalingParams['h'];
			}
		}
	}


	/**
 	 * Create Garp_Image_Scaler object
 	 * @return Garp_Image_Scaler
 	 */
	protected function _getImageScaler() {
		if (!$this->_scaler) {
			$this->_scaler = new Garp_Image_Scaler();
		}
		return $this->_scaler;
	}
}
