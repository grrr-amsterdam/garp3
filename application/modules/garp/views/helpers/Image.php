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
class G_View_Helper_Image extends Zend_View_Helper_Abstract {
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
				return Garp_Image_Scaler::getScaledUrl($image, $template);
			} else {
				$file = new Garp_Image_File();
				return $file->getUrl($image);
			}
		}
	}


	/**
	 * Returns the path to the dynamic image
	 * @param Mixed $image						Filename of the dynamic image, without path, id of the image record, or full (remote) url. 
	 * 											This can also be an instance of an Image model. If so, the image will
	 * 											be rendered inside a partial that includes its caption and other metadata.
	 * @param Array $template					Scaling parameters or template name. For a list of scaling params, see the constructor of Garp_Image_Scaler.
	 * @return String
	 */
	public function getPath($image, $template = null) {
		trigger_error("The ImageHelper::getPath() function is deprecated. Please use getUrl() from now on. You can call the method in the same manner.");
		return $this->getUrl($image, $template);
		// $imageFile = new Garp_Image_File();
		// 
		// if (
		// 	!is_null($template) &&
		// 	!empty($template)
		// ) {
		// 	return $imageFile->createTemplateScaledPath(
		// 		$this->_getIdFromRecord($image),
		// 		$template
		// 	);
		// } else {
		// 	return $imageFile->createSourcePath(
		// 		$this->_fetchFilenameFromRecord($image)
		// 	);
		// }
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

		if (!array_key_exists('alt', $htmlAttribs))
			$htmlAttribs['alt'] = '';

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
		$file = new Garp_Image_File('upload');
		$scaler = new Garp_Image_Scaler();

		if (
			!is_null($template) &&
			!empty($template)
		) {
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
			$src = $file->getUrl($filename);
		}

		if (!array_key_exists('alt', $htmlAttribs))
			$htmlAttribs['alt'] = '';

		$imgTag = $this->view->htmlImage($src, $htmlAttribs);
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

		$ini = Garp_Cache_Ini::factory(APPLICATION_PATH.'/configs/application.ini');
		if (
			is_null($ini->image->setHtmlHeight) ||
			$ini->image->setHtmlHeight
		) {
			if (
				array_key_exists('h', $scalingParams) &&
				$scalingParams['h']
			) {
				$htmlAttribs['height'] = $scalingParams['h'];
			}
		}
	}


	//DEPRECATED
	/**
	 * Determines whether the image is requested by database record; either by reference to the record itself, or its id.
	 * @param mixed $image Either a DB row, an id number, a filename or a url
	 * @return Boolean Whether the passed argument refers to an image record in the database (true), or merely a physical file (false)
	 */
	// private function _requireImageRecord($image) {
	// 	if (
	// 		!$image instanceof Garp_Db_Table_Row &&
	// 		!is_numeric($image)
	// 	) {
	// 		throw new Garp_View_Helper_Exception(
	// 			__METHOD__.' expects the first parameter to be either'.
	// 			' an instance of Garp_Model_Image or an Image record id number.'
	// 		);
	// 	}
	// }

	//DEPRECATED
	/**
	 * Returns id number when row object or id number is provided.
	 * @param mixed $image Either a DB row or an id number
	 * @return Int Id number of this Image record
	 */
	// private function _getIdFromRecord($image) {
	// 	if ($image instanceof Garp_Db_Table_Row) {
	// 		return $image->id;
	// 	} elseif (is_numeric($image)) {
	// 		return $image;
	// 	} else {
	// 		throw new Garp_View_Helper_Exception(
	// 			__METHOD__.' expects the first parameter to be either'.
	// 			' an instance of Garp_Model_Image or an Image record id number.'
	// 		);
	// 	}
	// }


	//	DEPRECATED
	// /**
	//  * Fetches filename from database record
	//  * @param mixed $image		Either an id number or a record object
	//  * @return String			Filename of this image
	//  */
	// private function _fetchFilenameFromRecord($image) {
	// 	if ($image instanceof Garp_Db_Table_Row) {
	// 		//	arg is Image record
	// 		return $image->filename;
	// 	} elseif (is_numeric($image)) {
	// 		//	arg is Image record id, so fetch filename from database
	// 		$imageModel = new G_Model_Image();
	// 		$imageRecord = $imageModel->fetchRow(
	// 			$imageModel->select()->where('id = ?', $image)
	// 		);
	// 		if (empty($imageRecord->filename)) {
	// 			throw new Garp_View_Helper_Exception(__METHOD__.' could not fetch a filename from this id number.');
	// 		} else return $imageRecord->filename;
	// 	} else throw new Garp_View_Helper_Exception(__METHOD__.' expects image to be an id number or a record object.');
	// }
	
	
	/**
	 * DEPRECATED
	 * Returns the path to the dynamic image
	 * @param Mixed $image						Filename of the dynamic image, without path, id of the image record, or full (remote) url. 
	 * 											This can also be an instance of an Image model. If so, the image will
	 * 											be rendered inside a partial that includes its caption and other metadata.
	 * @param Array $scalingParamsOrTemplate	Scaling parameters or template name. For a list of scaling params, see the constructor of Garp_Image_Scaler.
	 * @return String
	 */
	// public function ____________________getPath($image, $scalingParamsOrTemplate = null) {		
	// 	$imageFile = new Garp_Image_File();
	// 
	// 	//	determine whether this image is requested by db record or filename
	// 	$isImageRecord = $this->_isImageRecord($image);
	// 	
	// 	if (
	// 		!is_null($scalingParamsOrTemplate) &&
	// 		!empty($scalingParamsOrTemplate)
	// 	) {
	// 		$isCustomScalingRequest = $this->_isCustomScalingRequest($scalingParamsOrTemplate);
	// 
	// 		if (
	// 			$isImageRecord &&
	// 			!$isCustomScalingRequest
	// 		) {
	// 			//	pre-cached
	// 			return $imageFile->createTemplateScaledPath(
	// 				$this->_getIdFromRecord($image),
	// 				$scalingParamsOrTemplate
	// 			);
	// 		} else {
	// 			//	pragmatically cached
	// 			$filename = $isImageRecord ? 
	// 				$this->_fetchFilenameFromRecord($image) :
	// 				$image;
	// 			
	// 			if (!$isCustomScalingRequest) {
	// 				$scaler = new Garp_Image_Scaler();
	// 				$scalingParamsOrTemplate = $scaler->getTemplateParameters($scalingParamsOrTemplate);
	// 			}
	// 			$cacheFile = $imageFile->createCustomScaledPath($filename, $scalingParamsOrTemplate, true);
	// 			if (!$imageFile->exists($cacheFile)) {
	// 				$imageFile->scaleCustomAndStore($filename, $scalingParamsOrTemplate);
	// 			}
	// 
	// 			return $imageFile->createCustomScaledPath(
	// 				$filename,
	// 				$scalingParamsOrTemplate
	// 			);
	// 		}
	// 	} else {
	// 		//	source image is requested without
	// 		return $imageFile->createSourcePath(
	// 			$isImageRecord ? 
	// 				$this->_fetchFilenameFromRecord($image) :
	// 				$image
	// 		);
	// 	}
	// }


	// /**
	//  * Determines whether the image is requested by database record; either by reference to the record itself, or its id.
	//  * @param mixed $image Either a DB row, an id number, a filename or a url
	//  * @return Boolean Whether the passed argument refers to an image record in the database (true), or merely a physical file (false)
	//  */
	// private function _isImageRecord($image) {
	// 	if (
	// 		$image instanceof Garp_Db_Table_Row ||
	// 		is_numeric($image)
	// 	) {
	// 		return true;
	// 	} elseif (is_string($image)) {
	// 		return false;
	// 	} else {
	// 		throw new Garp_View_Helper_Exception(
	// 			__METHOD__.' expects the first parameter to be either'.
	// 			' a filename, a url to an image, an instance of Garp_Model_Image or an Image record id number.'
	// 		);
	// 	}
	// }


	//	DEPRECATED
	/**
	 * Checks whether the argument indicates a custom, pragmatic scaling request, as opposed to scaling by predefined template.
	 * @param mixed $scalingParamsOrTemplate Either an array of scaling options, or a template name
	 * @return Boolean Returns true if this request requires custom scaling, and false if this image should be scaled by template
	 */
	// private function _isCustomScalingRequest($scalingParamsOrTemplate) {
	// 	if (is_array($scalingParamsOrTemplate)) {
	// 		return true;
	// 	} elseif (is_string($scalingParamsOrTemplate)) {
	// 		return false;
	// 	} else throw new Garp_View_Helper_Exception('Scaling should be indicated by either custom scaling parameters or a template name.');
	// }

}