<?php
/**
 * Stores, caches and retrieves images from disk or remote location.
 * Decides whether a new scaled and cached image should be generated.
 * @author David Spreekmeester | Grrr.nl
 * @package Garp
 */
class Garp_Image_File extends Garp_File {
	/**
	 * @var Array $extensions Numeric array of image types, where the keys are PHP native constants used in getImageSize(), and their values are file extensions.
	 */
	protected $_extensions = array(
		IMAGETYPE_GIF => 'gif',
		IMAGETYPE_JPEG => 'jpg',
		IMAGETYPE_PNG => 'png'
	);


	public function __construct($uploadOrStatic = null) {
		parent::__construct('image', $uploadOrStatic);
	}

	public function store($filename, $data, $overwrite = false, $formatFilename = true) {
		$returnedParams = $this->_beforeStore($filename, $data, $overwrite, $formatFilename);
		list($filename, $data, $overwrite, $formatFilename) = $returnedParams;

		$result = parent::store($filename, $data, $overwrite, $formatFilename);
		
		return $result;
	}

	public function getImageType($filename) {
		$extension = $this->_getExtension($filename);
		$type = strcasecmp($extension, 'jpeg') === 0 ?
			IMAGETYPE_JPEG :
			array_search(strtolower($extension), $this->_extensions)
		;

		if ($type !== false)
			return $type;
		else throw new Exception("Can not find the proper image type for extension '{$extension}'.");
	}

	/**
	 * Lets the browser render an image file
	 * @param String $path The path to the image file
	 * @param String $timestamp Cache timestamp - if not provided, this will have to be found out (at the cost of disk access)
	 * @param String $mime The image mimetype - if not provided, this will have to be found out (at the cost of disk access)
	 * @return Void
	 */
	public function show($path, $timestamp = null, $mime = null) {
		$headers = function_exists('apache_request_headers') ?
			apache_request_headers() :
			array();

	    if (is_null($timestamp))
			$timestamp = $this->_readTimestampFromFile($path);
		if (is_null($mime)) {
			$mime = $this->_readMimeTypeFromFile($path);
		}

		header("Content-Type: $mime");
		header("Cache-Control: maxage=".(24*60*60).', must-revalidate'); //In seconds
		header("Pragma: public");

	    // Checking if the client is validating his cache and if it is current.
	    if (
			isset($headers['If-Modified-Since']) &&
			strtotime($headers['If-Modified-Since']) == $timestamp
		) {
	        // Client's cache IS current, so we just respond '304 Not Modified'.
	        header('Last-Modified: '.gmdate('D, d M Y H:i:s', $timestamp).' GMT', true, 304);
	    } else {
	        // Image not cached or cache outdated, we respond '200 OK' and output the image.
	        header('Last-Modified: '.gmdate('D, d M Y H:i:s', $timestamp).' GMT', true, 200);
	        header('Content-Length: '.filesize($path));
	        $resource = fopen($path, 'rb');
			rewind($resource);
			fpassthru($resource);
			fclose($resource);
	    }
	}

	public function getAllowedExtensions() {
		$allowedExtensions = array_values($this->_extensions);
		$allowedExtensions[] = 'jpeg';
		return $allowedExtensions;
	}

	/**
	 * @return Array The passed parameters.
	 */
	protected function _beforeStore($filename, $data, $overwrite, $formatFilename) {
		if ($formatFilename) {
			$filename = $this->_correctExtension($filename);
		}

		if ($this->_pngQuantIsEnabled()) {	
			$pngQuant = new Garp_Image_PngQuant();
			if ($pngQuant->isAvailable()) {
				$data = $pngQuant->optimizeData($data);
			}
		}
		
		return array($filename, $data, $overwrite, $formatFilename);
	}

	/**
 	 * Checks if pngQuant is explicitly disabled for this project, to prevent exec() calls.
 	 */
	protected function _pngQuantIsEnabled() {
		$ini = Zend_Registry::get('config');
		if (isset($ini->pngquant) && isset($ini->pngquant->enabled)) {
			return $ini->pngquant->enabled;
		}

		return true;
	}

	protected function _correctExtension($filename) {
		$oldExtension = $this->_getExtension($filename);
		$newExtension = $oldExtension === 'jpeg' ? 'jpg' : $oldExtension;
		return
			substr($filename, 0, strlen($filename) - strlen($oldExtension))
			.$newExtension;
	}


	/**
	 * Determines whether this path is a full url, including protocol.
	 * @param	String		$path	Path or url to file
	 * @return	Boolean				True if this path is a full url
	 */
	private function _isUrl($path) {
		return strpos($path, '://') !== false;
	}


	private function _checkUrlFormat($url) {
		if (strpos($url, '://') === false)
			throw new Exception('This file is not a valid url.');
	}
}
