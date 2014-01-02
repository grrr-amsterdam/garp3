<?php
/**
 * Stores, caches and retrieves images from disk or remote location.
 * Decides whether a new scaled and cached image should be generated.
 * @author David Spreekmeester | Grrr.nl
 * @package Garp
 */
class Garp_Image_File extends Garp_File {
	/**
	 * @var Array Image configuration parameters.
	 */
	// private $_config;


	/**
	 * @var Array $_cdn	Content Delivery Network config settings.
	 */
	// private $_cdn;


	/**
	 * @var String $baseUrl Relative path to the app's base
	 */
	// private $_baseUrl;


	/**
	 * @var Array $extensions Numeric array of image types, where the keys are PHP native constants used in getImageSize(), and their values are file extensions.
	 */
	protected $_extensions = array(
		IMAGETYPE_GIF => 'gif',
		IMAGETYPE_JPEG => 'jpg',
		IMAGETYPE_PNG => 'png'
	);


	/**
	 * @var Int $remoteDownloadTimeout  Maximum number of seconds to use when attempting to download
	 * 									a remotely located image to the local server cache.
	 */
	// private $_remoteDownloadTimeout = 30;


	public function __construct($uploadOrStatic = null) {
		// $ini = Garp_Cache_Ini::factory(APPLICATION_PATH.'/configs/application.ini');
		// $this->_config = $ini->image;
		// $this->_cdn = $ini->cdn;
		// $this->_baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
		
		parent::__construct('image', $uploadOrStatic);
	}


	public function __call($method, $args) {
		if ($method == 'store') {
			$filename = $args[0];
			$formatFilename = $args[3];
			if ($formatFilename)
				$this->_correctExtension($filename);
		}

		return parent::__call($method, $args);
	}
	


	//TODO
	//	DEPRECATED?
	/**
	 * Creates path to the image source file, without taking any scaling manipulation parameters into consideration.
	 * @param	String		$filename		Filename of the image
	 * @param	Boolean		$absolute		Whether to return the absolute (system) path for file access, or the relative path for HTTP access
	 * @return	String
	 */
	// public function createSourcePath($filename, $absolute = false) {
	// 	$folder = $absolute ?
	// 		$this->_config->path->upload :
	// 		$this->_baseUrl.$this->_config->uri->upload
	// 	;
	// 	$path = $folder.$filename;
	// 	return $absolute ?
	// 		$path :
	// 		'http://'.$this->_cdn->host.
	// 		(
	// 			$this->_cdn->type === 's3' ?
	// 				'/'.$this->_cdn->s3->bucket:
	// 				''
	// 		).
	// 		$path
	// 	;
	// }


	//TODO
	// public function createTemplateScaledPath($id, $template, $absolute = false) {
	// 	Zend_Debug::dump($this->getScaledUrl($id, $template));
	// 	exit;
	// 	
	// 	
	// 	
	// 	$folder = $absolute ? $this->_config->path->scaled : $this->_config->uri->scaled;
	// 	$path = $this->_baseUrl.$folder.$template.'/'.$id;
	// 
	// 	return ($absolute ?
	// 		$path :
	// 		'http://'.$this->_cdn->host.$path
	// 	);
	// }


	public function getImageType($filename) {
		$extension = $this->_getExtension($filename);
		$type = $extension === 'jpeg' ?
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


	protected function _correctExtension($filename) {
		$oldExtension = $this->_getExtension($filename);
		$newExtension = $oldExtension === 'jpeg' ? 'jpg' : $oldExtension;
		return
			substr($filename, 0, strlen($filename) - strlen($oldExtension))
			.$newExtension;
	}


	protected function _getAllowedExtensions() {
		return array_values($this->_extensions);
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


	// DEPRECATED
	/**
	 * Checks whether we support scaling this image type, and whether width and height of the source image are readable.
	 * @param	Array	$imageInfo	The getimagesize() result of the source image.
	 * @return	Void				Throws an exception if image is not valid.
	 */
	// private function _checkImageValidity(Array &$imageInfo) {
	// 	if (!array_key_exists($imageInfo[2], $this->_extensions)) {
	// 		$mimeString = image_type_to_mime_type($imageInfo[2]);
	// 		throw new Exception(
	// 			'Sorry, this image type ('
	// 			.(!empty($mimeString) ? $mimeString : 'unknown')
	// 			.') is not supported.'
	// 		);
	// 	}
	// 	if (!$imageInfo[0] || !$imageInfo[1])
	// 		throw new Exception(
	// 			'Sorry, this image type does not seem to have correct dimension attributes.'
	// 		);
	// 	if (!array_key_exists('mime', $imageInfo) || empty($imageInfo['mime']))
	// 		throw new Exception(
	// 			'Sorry, it seems impossible to retrieve the mime type for this image.'
	// 		);
	// }

	
	//DEPRECATED
	// public function scaleCustomAndStore($filename, $scaleParams) {
	// 	if ($this->_hasAllowedExtension($filename)) {
	// 		$sourcePath = $this->createSourcePath($filename, true);
	// 		$imageScaler = new Garp_Image_Scaler();
	// 		$scaledImageDataArray = $imageScaler->scale($sourcePath, $scaleParams);
	// 		$customScaledPath = $this->createCustomScaledPath($filename, $scaleParams, true);
	// 
	// 		$this->_storeImageData($customScaledPath, $scaledImageDataArray['resource']);
	// 	} else throw new Exception('Sorry, I can\'t scale this type of file.');
	// }
	

	//DEPRECATED
	// public function createCustomScaledPath($filename, Array $scaleParams, $absolute = false) {
	// 	$parameterString = $this->_createFileParameterString($scaleParams);
	// 
	// 	$folder = $absolute ?
	// 		$this->_config->path->scaled :
	// 		$this->_baseUrl.$this->_config->uri->scaled
	// 	;
	// 	$path = $folder.'custom/'.$filename.$parameterString;
	// 
	// 	return ($absolute ?
	// 		$path :
	// 		'http://'.$this->_cdn->host.
	// 		(
	// 			$this->_cdn->type === 's3' ?
	// 				'/'.$this->_cdn->s3->bucket:
	// 				''
	// 		).
	// 		$path
	// 	);
	// }



//	public function storeScaled($sourcePath, $scaleParams) {
		//exit('--storing scaled - '.$sourcePath.'<br>'.print_r($scaledParams, true));
		// $imageScaler = new Garp_Image_Scaler();
		// print '<pre>';
		// print_r($imageScaler->scale($sourcePath, $scaleParams));
		// print '</pre>';
		// exit;
		
//		$image = $imageScaler->render($filename);
		//TODO
//		$this->_store($image['path'], $image['timestamp'], $image['mime']);
//	}


	/**
	 * Provides the relative HTTP path to an existing image file, whether symbolic or physical.
	 * Decides which path is appropriate. 
	 * @param	String		$path		Url or filename of the image
	 * @param	Array		$params		Parameters to set scale manipulation of the image output
	 * @return	String					Relative http path
	 */
//TODO: implement id / tpl route
//TODO: implement remote downloading
		/*
	//	¿DEPRECATED?
	public function getPath($path, Array $params = array()) {
		// if ($this->_isUrlOfLocalFile($path)) {
		// 	$filename = basename($path);
		// } elseif ($this->_isUrl($path)) {
		// 	//	the Image helper is filing a request to render a remote image 
		// 	$filename = $this->_sluggifyUrl($path, $params);
		// 
		// 	if (!$this->sourceExists($filename)) {
		// 		//	this remote image is not downloaded yet, so render the path to the
		// 		//	image controller, telling it to download the file when called upon.
		// 		$params['remote'] = 1;
		// 		return $this->_createDynamicScalerPath($this->_encodeUrl($path), $params);
		// 	}
		// } else
			$filename = $path;

		if (!isset($params['w']) && !isset($params['h'])) {
			//	no width or height was specified, so return the url to the original image
			return $this->createSourcePath($filename, false);
		} else {
			$cachePath = $this->createCustomScaledPath($filename, $params, true);

			if (!$this->exists($cachePath, $params)) {
				//	the cached version of this image does not exist yet,
				$this->scaleCustomAndStore($filename, $params);
			}

			return $this->createCustomScaledPath($filename, $params, false);
		}
	}
	*/


	/**
	 * Creates full path including parameters to store / retrieve the cache file as.
	 * @param	String		$filename		Filename of the image
	 * @param	Array		$inputParams	Parameters given to set scale manipulation of the image output.
	 * 										IMPORTANT: Only the parameters that were set by the context should be provided, no defaults or complemented ones.
	 * @param	Boolean		$absolute		Whether to return the absolute (system) path for file access, or the relative path for HTTP access
	 * @return	String
	 */
//TODO
	// public function createCachePath($filename, Array $inputParams, $absolute = false) {
	// 	if (
	// 		(!array_key_exists('w', $inputParams) || empty($inputParams['w'])) &&
	// 		(!array_key_exists('h', $inputParams) || empty($inputParams['h']))
	// 	) {
	// 		//	there was no desired width and height provided, so return the path to the source image
	// 		$folder = $absolute ? $this->_config->path->upload : $this->_config->uri->upload;
	// 		return ($absolute ? $folder.$filename : $this->_baseUrl.$folder.$filename);
	// 	} else {
	// 		//	return the path to the cached image file, whether it exists or not
	// 		$parameterString = '_';
	// 		foreach ($this->args as $paramName) {
	// 			if (
	// 				$paramName !== 'cache' &&
	// 				array_key_exists($paramName, $inputParams) &&
	// 				!empty($inputParams[$paramName])
	// 			) {
	// 				$parameterString .= '_'.$paramName.'-'.(string)$inputParams[$paramName];
	// 			}
	// 		}
	// 
	// 		$folder = $absolute ? $this->_config->path->scaled : $this->_config->uri->scaled;
	// 		$path = $folder.$filename.$parameterString;
	// 
	// 		return ($absolute ?
	// 			$path :
	// 			$this->_config->host->static.$path
	// 		);
	// 	}
	// }


	/**
	* DEPRECATED
	 * Returns mime type. Use sparingly; costs disc access.
	 * @param unknown_type $fullPath
	 * @return unknown_type
	 */
	// private function _readMimeTypeFromFile($path) {
	// 	if ($this->_cdn->type === 's3') {
	// 		return $this->_s3->getMime($path);
	// 	} else {
	// 		$imageInfo = @getimagesize($path);
	// 
	// 		if (is_array($imageInfo))
	// 			return $imageInfo['mime'];	
	// 	}
	// 	
	// 	throw new Exception('Could not detect image mimetype from '.$path);
	// }
	

	//	DEPRECATED
	// private function _readTimestampFromFile($path) {
	// 	if ($this->_cdn->type === 'S3') {
	// 		return $this->_s3->getTimestamp($path);
	// 	} else {
	// 		return filemtime($path);
	// 	}
	// }
	

	/**
	 * Stores a remotely downloaded image. Throws exception if this did not succeed.
	 * @param String $url		The full url to the remote image
	 * @param String $content	The content of the remote file as a string
	 * @return Void
	 */
	// private function _storeRemoteImage($url, $content) {
	// 	$path = $this->_config->path->upload.$this->_sluggifyUrl($url);
	// 	$bytesWritten = file_put_contents($path, $content);
	// 	if ($bytesWritten === false)
	// 		throw new Exception('Could not store remote file content.');
	// }

//DEPRECATED
//TODO: file_put_contents moet ook nog voor S3 geïmplementeerd worden.
	// private function _storeImageData($path, $data) {
	// 	$dir = dirname($path);
	// 	if (!file_exists($dir)) {
	// 		mkdir($dir, 0774, true);
	// 	}
	// 
	// 	$bytesWritten = file_put_contents($path, $data);
	// 	if ($bytesWritten === false)
	// 		throw new Exception('Could not store file in '.$path);
	// }


	/**
	 * Throws an exception if the cache folder is not writable.
	 * @return Void
	 */
//TODO - is hopelijk niet meer nodig, moet alleen door deze class gebruikt worden.
	// public function checkCacheFolderWritability() {
	// 	if (!is_writable($this->_config->path->scaled))
	// 		throw new Exception(
	// 			'The cache folder for images ('
	// 			.$this->_config->path->scaled
	// 			.') is not writable.'
	// 		);
	// }


	/**
	 * Throws an exception if the upload folder is not writable.
	 * @return Void
	 */
//DEPRECATED
//TODO - deze moet alleen aangeroepen worden bij lokaal CDN
	// private function _checkUploadFolderWritability() {
	// 	if (!is_writable($this->_config->path->upload))
	// 		throw new Exception(
	// 			'The upload folder for images ('
	// 			.$this->_config->path->upload
	// 			.') is not writable.'
	// 		);
	// }


	/**
	 * Checks whether this file has an allowed extension, case-insensitively.
	 */
//DEPRECATED
	// private function _hasAllowedExtension($file) {
	// 	$fileComponents = explode('.', $file);
	// 	$extension = $fileComponents[sizeof($fileComponents)-1];
	// 	
	// 	// Allow for JPEG
	// 	if ($extension == 'jpeg') {
	// 		$extension = 'jpg';
	// 	}
	// 	foreach ($this->_extensions as $allowedExtension) {
	// 		if (strcasecmp($extension, $allowedExtension) === 0)
	// 			return true;
	// 	}
	// 	return false;
	// }



	/**
	 * Gets mime type from extension, used for performance, when the image itself is not analyzed.
	 * Note! This requires the file extension to be correct, since it's not an integral file check, and can be fooled.
	 * Also, it only works with extensions defined in this component, in $this->_extensions.
	 * @return String $mime The mime type
	 */
	// public function getMimeFromExtension($filename) {
	// 	$extension = pathinfo($filename, PATHINFO_EXTENSION);
	// 	if ($imageType = array_search(strtolower($extension), $this->_extensions)) {
	// 		return image_type_to_mime_type($imageType);
	// 	} else throw new Exception('Sorry, this extension is not a registered format.');
	// }




	//DEPRECATED?
	/**
	 * Creates a symbolic relative HTTP path to instruct the dynamic image scaler to create the cached and scaled file, and return the contents of the scaled image to the browser output.
	 * @param	String		$filename		Filename of the image
	 * @param	Array		$params			Parameters to set scale manipulation of the image output
	 * @return	String
	 */
	// private function _createDynamicScalerPath($filename, Array &$params) {
	// 	return $this->_baseUrl.$this->_config->uri->dynamic
	// 		.$filename.$this->_createUrlParameterString($params);
	// }
	

	//DEPRECATED
	/**
	 * Creates a string of the scaling parameters, to be used in a cached filename.
	 * @param Array $params Parameters to set scale manipulation of the image output
	 * @return String Parameter string
	 */
	// private function _createUrlParameterString(Array &$params) {
	// 	$paramString = '';
	// 	foreach ($params as $paramName => $paramVal) {
	// 		if (!is_null($paramVal))
	// 			$paramString .= '/'.$paramName.'/'.$paramVal;
	// 	}
	// 	return $paramString;
	// }


	//DEPRECATED
	// private function _createFileParameterString(Array &$params) {
	// 	$paramString = '__';
	// 	foreach ($params as $paramName => $paramVal) {
	// 		if (!is_null($paramVal))
	// 			$paramString .= '_'.$paramName.'-'.$paramVal;
	// 	}
	// 	return $paramString;
	// }


	//DEPRECATED?
	// private function _encodeUrl($url) {
	// 	return urlencode(base64_encode($url));
	// }


	//DEPRECATED?
	// private function _decodeUrl($encodedUrl) {
	// 	return base64_decode(urldecode($encodedUrl));
	// }


	//DEPRECATED?
	// private function _sluggifyUrl($url, Array &$params = array()) {
	// 	return 'remote_'.md5($url).$this->_createUrlParameterString($params);
	// }


	//TODO of DEPRECATED?
	/**
	 * Determines whether this path is an url pointing to a file in the local (dynamic) images folder.
	 * @param	String		$path	File or url to an image file
	 * @return	Boolean				True if this file is situated in the local (dynamic) images folder
	 */
	// private function _isUrlOfLocalFile($path) {
	// 	//TODO
	// 	$urlEquivalentOfLocalFolder = 'http://'.$_SERVER['HTTP_HOST']
	// 		.$this->_baseUrl.$this->_config->uri->dynamic;
	// 	return strpos($path, $urlEquivalentOfLocalFolder) !== false;
	// }


	// TODO of DEPRECATED?
	/**
	 * Downloads a remote image and stores it in the uploads folder.
	 * @param	String	$url
	 * @return	String	$urlSlug	The slug (filename) that this remote image is stored as
	 */
 	// public function downloadRemoteImage($encodedUrl) {
	// 	$url = $this->_decodeUrl($encodedUrl);
	// 
	// 	$this->_checkUploadFolderWritability();
	// 	$this->_checkUrlFormat($url);
	// 
	// 	$urlSlug = $this->_sluggifyUrl($url);
	// 
	// 	$protocol = parse_url($url, PHP_URL_SCHEME);
	// 	$options = array($protocol =>
	//     		array(
	//         		'timeout' => $this->_remoteDownloadTimeout
	//     		)
	// 	);
	// 	$context  = stream_context_create($options);
	// 	$content = file_get_contents($url, false, $context);
	// 
	// 	if ($content !== false) {
	// 		$this->_storeRemoteImage($url, $content);
	// 
	// 		$imageInfo = getImageSize($this->_config->path->upload.$urlSlug);
	// 		$this->checkImageValidity($imageInfo);
	// 	} else {
	// 		/*	could not retrieve remote file content!
	// 		 * 	use the local 'missing image' file instead 
	// 		 */
	// 		$missingImagePath = $this->_config->path->upload.$this->_config->filename->missingImage;
	// 		$contentReplacement = file_get_contents($missingImagePath);
	// 		$this->_storeRemoteImage($url, $contentReplacement);
	// 	}
	// 
	// 	return $urlSlug;
	// }

}