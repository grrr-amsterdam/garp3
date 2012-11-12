<?php
/**
 * Garp_Controller_Helper_Upload
 * Bundles file upload functionality
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class Garp_Controller_Helper_Upload extends Zend_Controller_Action_Helper_Abstract {
	/**
	 * For optimalization purposes, we store file handlers, using
	 * the uploadType (f.i. Garp_File::TYPE_IMAGES) as array key.
	 */
	$_fileHandlers = array();
	
	
	/**
 	 * Shortcut to self::uploadFromFiles().
 	 * Call from controller $this->_helper->upload()
 	 * @param String $uploadType The type of file being uploaded, either 
 	 * 'images' or 'documents'
 	 * @param String $singleFormKey Upload one specific file, or all available 
 	 * in $_FILES
 	 * @param Boolean $allowBlank Wether blank filenames are supported
 	 */
	public function direct($uploadType = Garp_File::TYPE_IMAGES, $singleFormKey = null, $allowBlank = false) {
		return $this->uploadFromFiles($uploadType, $singleFormKey, $allowBlank);
	}


	/**
 	 * Upload a file. Uses the $_FILES array.
 	 * @param String $uploadType The type of file being uploaded, either 
 	 * 'images' or 'documents'
 	 * @param String $singleFormKey Upload one specific file, or all available 
 	 * in $_FILES
 	 * @param Boolean $allowBlank Wether blank filenames are supported
	 * @return Array Collection of new filenames
	 */
	public function uploadFromFiles($uploadType = Garp_File::TYPE_IMAGES, $singleFormKey = null, $allowBlank = false) {
		$filesToUpload = $singleFormKey ? array($singleFormKey => $_FILES[$singleFormKey]) : $_FILES;
		$newFilenames = array();

		foreach ($filesToUpload as $formKey => $fileParams) {
			$isArray = is_array($fileParams['tmp_name']);
			if (!$isArray) {
				// Generalize interface: always expect array
				$this->_castToArray($fileParams);
			}
			$uploadedFilesByTheSameName = count($fileParams['tmp_name']);
			for ($i = 0; $i < $uploadedFilesByTheSameName; $i++) {
				$name    = !empty($fileParams['name'][$i])     ? $fileParams['name'][$i]     : null;
				$tmpName = !empty($fileParams['tmp_name'][$i]) ? $fileParams['tmp_name'][$i] : null;
				
				if (!empty($tmpName) || $allowBlank) {
					if (is_uploaded_file($tmpName)) {
						$newFilename = $this->_store($uploadType, $name, file_get_contents($tmpName));
						if ($newFilename) {
							if ($isArray) {
								$newFilenames[$formKey][] = $newFilename;
							} else {
								$newFilenames[$formKey] = $newFilename;
							}
						}
					} else {
						throw new Exception($formKey.'['.$i.'] is not an uploaded file.');
					}
				}
			}
		}
		return $newFilenames;
	}


	/**
 	 * Cast uploaded data to Array.
 	 * We allow arrays, where the data is nested like so:
 	 * $_FILES['name'][0] = 'foo.jpg'
 	 * $_FILES['name'][1] = 'bar.jpg'
 	 * $_FILES['tmp_name'][0] = 'foo.jpg'
 	 * $_FILES['tmp_name'][1] = 'bar.jpg'
 	 * This method provides a generic interface to the $_FILES array by ensuring 
 	 * the above structure even for single uploads.
 	 * @param Array $fileParams The result of $_FILES['something']
 	 * @return Void
 	 */
	protected function _castToArray(&$fileParams) {
		$fileParams['name']     = (array)$fileParams['name'];
		$fileParams['type']     = (array)$fileParams['type'];
		$fileParams['tmp_name'] = (array)$fileParams['tmp_name'];
		$fileParams['error']    = (array)$fileParams['error'];
		$fileParams['size']     = (array)$fileParams['size'];
	}


	/**
	 * Upload raw POST data.
	 * @param String $uploadType The type of file being uploaded, either 
 	 * 'images' or 'documents'
 	 * @param String $name The filename
 	 * @param String $bytes
 	 * @return Array Response is consistent with self::uploadFromFiles.
	 */
	public function uploadRaw($uploadType = Garp_File::TYPE_IMAGES, $filename, $bytes) {
		return array(
			$filename => $this->_store($uploadType, $filename, $bytes)
		);
	}


	/**
 	 * Store the uploaded bytes in a file.
 	 * @param String $uploadType The type of file being uploaded, either 
 	 * 'images' or 'documents'
 	 * @param String $name The filename
 	 * @param String $bytes
 	 * @return String The new filename
 	 */
	protected function _store($uploadType, $name, $bytes) {
		if (!array_key_exists($uploadType, $this->_fileHandlers)) {
			$this->_fileHandlers[$uploadType] = $uploadType === Garp_File::TYPE_IMAGES ?
				new Garp_Image_File() :
				new Garp_File($uploadType)
			;			
		}

		return $this->_fileHandlers[$uploadType]->store($name, $bytes);
	}
}
