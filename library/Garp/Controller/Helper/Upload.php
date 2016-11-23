<?php
/**
 * Garp_Controller_Helper_Upload
 * Bundles file upload functionality
 *
 * @package Garp_Controller_Helper
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Controller_Helper_Upload extends Zend_Controller_Action_Helper_Abstract {
    /**
     * For optimalization purposes, we store file handlers, using
     * the uploadType (f.i. Garp_File::TYPE_IMAGES) as array key.
     *
     * @var array
     */
    protected $_fileHandlers = array();

    /**
     * Shortcut to self::uploadFromFiles().
     * Call from controller $this->_helper->upload()
     *
     * @param string $uploadType    The type of file being uploaded, either
     *                              'images' or 'documents'
     * @param string $singleFormKey Upload one specific file, or all available in $_FILES
     * @param bool   $allowBlank    Wether blank filenames are supported
     * @param bool   $overwrite     Wether to overwrite existing files
     * @return array
     */
    public function direct(
        $uploadType = Garp_File::TYPE_IMAGES, $singleFormKey = null,
        $allowBlank = false, $overwrite = false
    ) {
        return $this->uploadFromFiles($uploadType, $singleFormKey, $allowBlank, $overwrite);
    }

    /**
     * Upload a file. Uses the $_FILES array.
     *
     * @param string $uploadType    The type of file being uploaded, either
     *                              'images' or 'documents'
     * @param string $singleFormKey Upload one specific file, or all available in $_FILES
     * @param bool   $allowBlank    Wether blank filenames are supported
     * @param bool   $overwrite     Wether to overwrite existing files
     * @return array Collection of new filenames
     */
    public function uploadFromFiles(
        $uploadType = Garp_File::TYPE_IMAGES, $singleFormKey = null,
        $allowBlank = false, $overwrite = false
    ) {
        $filesToUpload = $singleFormKey ?
            array($singleFormKey => $_FILES[$singleFormKey]) :
            $_FILES;
        $newFilenames = array();
        foreach ($filesToUpload as $formKey => $fileParams) {
            $isArray = is_array($fileParams['tmp_name']);
            if (!$isArray) {
                // Generalize interface: always expect array
                $this->_castToArray($fileParams);
            }
            // Keys might not be in order. This used to be a for loop, but extracting the
            // array keys like this allows us to use foreach without having to choke on
            // missing indexes (for instance when the keys are not 0 - 1 - 2 but 0 - 3 - 5).
            $keys = array_keys($fileParams['tmp_name']);
            foreach ($keys as $i) {
                $name    = !empty($fileParams['name'][$i])     ? $fileParams['name'][$i]     : null;
                $tmpName = !empty($fileParams['tmp_name'][$i]) ? $fileParams['tmp_name'][$i] : null;
                if (empty($tmpName) && !$allowBlank) {
                    continue;
                }
                $tmpName = is_array($tmpName) ? current($tmpName) : $tmpName;
                $name    = is_array($name) ? current($name) : $name;
                if (!is_uploaded_file($tmpName)) {
                    throw new Exception($formKey . '[' . $i . '] is not an uploaded file.');
                }
                $newFilename = $this->_store(
                    $uploadType,
                    $name,
                    file_get_contents($tmpName),
                    $overwrite
                );
                if ($newFilename) {
                    if ($isArray) {
                        $newFilenames[$formKey][$i] = $newFilename;
                    } else {
                        $newFilenames[$formKey] = $newFilename;
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
     *
     * @param array $fileParams The result of $_FILES['something']
     * @return void
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
     *
     * @param string $uploadType The type of file being uploaded, either 'images' or 'documents'
     * @param string $filename The filename
     * @param string $bytes
     * @return array Response is consistent with self::uploadFromFiles.
     */
    public function uploadRaw($uploadType, $filename, $bytes) {
        $uploadType = $uploadType ?: Garp_File::TYPE_IMAGES;
        return array(
            $filename => $this->_store($uploadType, $filename, $bytes)
        );
    }

    /**
     * Store the uploaded bytes in a file.
     *
     * @param string $uploadType The type of file being uploaded, either 'images' or 'documents'
     * @param string $name The filename
     * @param string $bytes
     * @param bool   $overwrite
     * @return string The new filename
     */
    protected function _store($uploadType, $name, $bytes, $overwrite = false) {
        if (!array_key_exists($uploadType, $this->_fileHandlers)) {
            $this->_fileHandlers[$uploadType] = $uploadType === Garp_File::TYPE_IMAGES ?
                new Garp_Image_File() :
                new Garp_File($uploadType);
        }
        return $this->_fileHandlers[$uploadType]->store($name, $bytes, $overwrite);
    }

}
