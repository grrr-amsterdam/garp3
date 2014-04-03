<?php
/**
 * Garp_Controller_Helper_ForceDownload
 * Force download dialog for a certain file.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class Garp_Controller_Helper_Download extends Zend_Controller_Action_Helper_Abstract {
	/**
	 * Force download dialog
	 * @param String $bytes The bytes that are to be downloaded
	 * @param String $filename The filename of the downloaded file
	 * @param Zend_Controller_Response_Abstract $response The response object
	 * @return Void
	 */
	public function force($bytes, $filename, Zend_Controller_Response_Abstract $response) {
		if (!strlen($bytes)) {
			$response->setBody('Sorry, we could not find the requested file.');
		} else {
			// Disable view and layout rendering
			Zend_Controller_Action_HelperBroker::getExistingHelper('viewRenderer')->setNoRender();
			Zend_Controller_Action_HelperBroker::getExistingHelper('layout')->disableLayout();
			
			// Process the download
			$this->_setHeaders($bytes, $filename, $response);
			$response->setBody($bytes);
		}
	}
	
	
	/**
	 * Set the neccessary headers for forcing the download dialog
	 * @param String $bytes The bytes that are to be downloaded
	 * @param String $filename The filename of the downloaded file
	 * @param Zend_Controller_Response_Abstract $response The response object
	 * @return Void
	 */
	protected function _setHeaders($bytes, $filename, Zend_Controller_Response_Abstract $response) {
		// fix for IE catching or PHP bug issue
		$response->setHeader('Pragma', 'public');
		// set expiration time
		$response->setHeader('Expires', '0'); 
		// browser must download file from server instead of cache
		$response->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
	
		// force download dialog
		$response->setHeader('Content-Type', 'application/force-download');
		$response->setHeader('Content-Type', 'application/octet-stream');
		$response->setHeader('Content-Type', 'application/download');
	
		// use the Content-Disposition header to supply a recommended filename and
		// force the browser to display the save dialog.
		$response->setHeader('Content-Disposition', 'attachment; filename="'.basename($filename).'"');

		/*
		The Content-transfer-encoding header should be binary, since the file will be read
		directly from the disk and the raw bytes passed to the downloading computer.
		The Content-length header is useful to set for downloads. The browser will be able to
		show a progress meter as a file downloads. The content-length can be determined by
		filesize function returns the size of a file.
		*/
		$response->setHeader('Content-Transfer-Encoding', 'binary');
		$response->setHeader('Content-Length', strlen($bytes));
	}
}