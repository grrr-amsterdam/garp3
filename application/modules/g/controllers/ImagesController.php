<?php
/**
 * G_ImagesController
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class G_ImagesController extends Garp_Controller_Action {
	protected $scaleParams = array(
		'w',
		'h',
		'bgcolor',
		'crop',
		'cropfocus',
		'grow',
		'remote'	// <-- moet dit niet een autodetect zijn?
	);
	
	
	/**
	 * Central entry point.
	 * @return Void
	 */
	public function indexAction() {
		exit('Browsing the list of images is not allowed.');
	}


	public function viewAction() {
		$request = $this->getRequest();
		$filename = $request->getParam('file');

		if (
			!$filename
		) {
			throw new Zend_Controller_Action_Exception('No filename was provided.', 404);
		} elseif (
			is_numeric($filename)
		) {
			if ($tpl = $request->getParam('tpl')) {
				throw new Zend_Controller_Action_Exception("Template-scaled images can no longer be acquired through this dynamic url. Please refer to the actual location of the file.", 404);
			} else {
				$this->_viewSourceById($filename);
			}
		} else throw new Zend_Controller_Action_Exception("Referring to images by their filename is no longer supported in this url format. Please use the image id.", 404);
	}


	private function _viewSourceById($id) {
		$imageModel = new G_Model_Image();
		$imageRow = $imageModel->fetchRow($imageModel->getAdapter()->quoteInto("id = ?", $id));
		if (count($imageRow) && isset($imageRow->filename)) {
			$file = new Garp_File('image');
			$url = $file->getUrl($imageRow->filename);
			header("Location: ".$url);
			exit;
		} else throw new Zend_Controller_Action_Exception("Sorry, I can't find the requested image.", 404);
	}


	/**
	 * DEPRECATED
	 * View an uploaded image, according to the parameters provided.
	 */
	// public function ____viewAction() {
	// 	$request = $this->getRequest();
	// 	$filename = $request->getParam('file');
	// 
	// 	//	decide the type of image request
	// 	if (
	// 		!$filename
	// 	) {
	// 		throw new Exception('No filename was provided.');
	// 	} elseif (
	// 		is_numeric($filename)
	// 	) {
	// 		if ($tpl = $request->getParam('tpl')) {
	// 			$this->_viewScaledByTemplate($filename, $tpl);
	// 		} else {
	// 			$this->_viewSourceById($filename);
	// 		}
	// 	} elseif (
	// 		!is_numeric($filename)
	// 	) {
	// 		$scaleParams = array();
	// 		$isInNeedOfScaling = false;
	// 
	// 		foreach ($this->scaleParams as $scaleParamName) {
	// 			$scaleParams[$scaleParamName] = $request->getParam($scaleParamName);
	// 			if (!is_null($scaleParams[$scaleParamName]))
	// 				$isInNeedOfScaling = true;
	// 		}
	// 
	// 		if ($isInNeedOfScaling)
	// 			$this->_viewCustomScaled($filename, $scaleParams);
	// 		else
	// 			$this->_viewSource($filename);
	// 
	// 	} else {
	// 		throw new Exception('Image request was malformed.');
	// 	}



// 		//	TODO ______________checken, aanpassen, fixen:
// 		$imageFile = new Garp_Image_File();
// 
// 		if (
// 			array_key_exists('remote', $scaleParams) &&
// 			$scaleParams['remote']
// 		) {
// 			//	this is a remote image that should first be downloaded to the local uploads folder
// 			try {
// 				$filename = $imageFile->downloadRemoteImage($filename);
// 			} catch (Exception $e) {
// 				exit($e->getMessage());
// 			}
// 		}
// 
// 		if (
// 			!(
// 				array_key_exists('cache', $scaleParams) &&
// 				!$scaleParams['cache']
// 			) &&
// 			$imageFile->cacheExists($filename, $scaleParams)
// 		) {
// 			/*	The scaled image cache file exists, so pass its content back to the browser.
// 				Warning: this is not a really efficient route since some extra disc access
// 				is necessary when the image is not already in the user's browser cache.
// 				Rendering the cache file path in the template with the image helper is
// 				strongly preferred.
// 			*/
// 			$cachePath = $imageFile->createCachePath($filename, $scaleParams, true);
// 			$this->output($cachePath);
// 			exit;
// 		} else {
// exit('nieuwe scale genereren');
// 			//	A new scaled cache file should be generated.
// 			$imageScaler = new Garp_Image_Scaler($scaleParams);
// 
// 			try {
// 				$image = $imageScaler->render($filename);
// 				$this->output($image['path'], $image['timestamp'], $image['mime']);
// 				exit;
// 			} catch(Exception $e) {
// 				exit($e->getMessage());
// 			}
// 		}
//	}


	/**
	 * Serve prescaled image, according to template
	 */
	// private function _viewScaledByTemplate($id, $template) {
	// 	$imageFile = new Garp_Image_File();
	// 	$imageFile->show(
	// 		$imageFile->createTemplateScaledPath($id, $template, true)
	// 	);
	// 	exit;
	// }


	/**
	 * Serve source image
	 */
	// private function _viewSource($filename) {
	// 	$imageFile = new Garp_Image_File();
	// 	$imageFile->show(
	// 		$imageFile->createSourcePath($filename, true)
	// 	);
	// 	exit;
	// }


	/** DEPRECATED */
	// private function _viewSourceById($id) {
	// 	$imageModel = new G_Model_Image();
	// 	$imageRow = $imageModel->fetchRow($imageModel->getAdapter()->quoteInto("id = ?", $id));
	// 	if (count($imageRow) && isset($imageRow->filename)) {
	// 		$this->_viewSource($imageRow->filename);
	// 	} else throw new Exception('Sorry, I can\'t find the requested image.');
	// }


	/**
	 * Serve custom-scaled image on the fly; if the scaled image does not exist, generate it first.
	 */
	// private function _viewCustomScaled($filename, $scaleParams) {
	// 	$imageFile = new Garp_Image_File();
	// 
	// 	if (
	// 		!$imageFile->exists(
	// 			$imageFile->createCustomScaledPath($filename, $scaleParams, true)
	// 		)
	// 	) {
	// 		//	A new scaled file should be generated.
	// 		$imageFile->scaleCustomAndStore(
	// 			$filename,
	// 			$scaleParams
	// 		);
	// 	}
	// 	
	// 	$imageFile->show(
	// 		$imageFile->createCustomScaledPath($filename, $scaleParams, true)
	// 	);
	// 	exit;
	// }
}