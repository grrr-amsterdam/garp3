<?php
/**
 * Garp_Image_Scaler
 * Scales images
 * @author David Spreekmeester | Grrr.nl
 * @package Garp
 **/
class Garp_Image_Scaler {
	/**
	 * @var Array $args Scaling behavior parameters
	 **/
	public $args = array(
		'w',
		'h',
		'bgcolor',
		'crop',
		'cropfocus',
		'grow'
	);


	/**
	 * @var String $name The scaled image's basename, including the correct extension
	 **/
	public $name = null;


	/**
	 * @var Array $params Parameters needed to render the scaled image, containing default settings, paths and overridable values.
	 **/
	private $params = array(
		'quality'		=> 80,
		'grow'			=> 1,
		'crop'			=> 1,
		'cropfocus'		=> 'face',
		'bgcolor'		=> '000000',	//	can be overridden in the ini file
		'mime'			=> null,
		'w'				=> null,
		'h'				=> null,
		'sourceWidth'	=> null,
		'sourceHeight'	=> null,
		'type'			=> null,
		'filter'		=> null
	);


	/**
	 * @var Array $_config	Array that represents the image config settings from an application specific ini file.
	 **/
	private static $_config = array();


	/**
	 * @var Array $inputParams Parameters that were provided during the request, i.e. without any defaults or complemented values.
	 **/
	private $inputParams = array();


	const SCALED_FOLDER = 'scaled';


	/**
	 * @param	Array	$params			Optional parameters to describe the desired format of the scaled image.
	 * @param	Int			[w]			Width of the scaled image. If not provided, the original width will be maintained.
	 * @param	Int			[h]			Height of the  scaled image. If not provided, the original height will be maintained.
	 * @param	String		[bgcolor]	Hexadecimal code of the canvas' background color.
	 * @param	Int			[quality]	Quality indication, between 0 and 100.
	 * @param	Boolean		[grow]		Sets whether the scaled image should ever be able to grow larger than the original.
	 * @param	Boolean		[crop]		Sets whether the scaled image should ever be allowed to be cropped, if the canvas is smaller in any dimension than the original.
	 * @param	String		[cropfocus]	Set to 'face' when a cropped picture should try to focus on the expected location of a face, or to 'center' to center the crop.
	 * @param	Boolean		[cache]		Sets whether a cached version of this image should be written, and if it should be used when it's available.
	 **/
	public function __construct() {
		$this->_loadIniDefaults();
	}


	/**
	 * Renders a scaled version of the image referenced by the provided filename, taken any (optional) manipulators into consideration.
	 * @param	String	$sourceData		The binary data of the original source image.
	 * @param 	Array 	$scaleParams
	 * @param 	Int 	$imageType		One of the PHP image type constants, such as IMAGETYPE_JPEG
	 * @return	Array
	 * 					['resource']	The image file data string
	 * 					['mime']		Mime type of the generated cache file
	 * 					['timestamp']	Timestamp of the generated cache file
	 **/
	public function scale($sourceData, $scaleParams, $imageType) {
		$this->_setInputParams($scaleParams);
		$mem = new Garp_Util_Memory();
		$mem->useHighMemory();

		if (strlen($sourceData) == 0) {
			throw new Exception("This is an empty file!");
		}
		
		if (!($source = imagecreatefromstring($sourceData))) {
			$finfo = new finfo(FILEINFO_MIME);
			$mime = $finfo->buffer($sourceData);
			throw new Exception("This source image could not be scaled. It's probably not a valid file type. Instead, this file is of the following type: " . $mime);
		}

		$this->_analyzeSourceImage($source, $imageType);
		$this->_addOmittedCanvasDimension();

		if ($this->_isFilterDefined($scaleParams)) {
			Garp_Image_Filter::filter($source, $scaleParams['filter']);
		}

		if ($this->_isSourceEqualToTarget($scaleParams)) {
			$outputImage = $sourceData;
		} else {
			$canvas = $this->_createCanvasImage($imageType);

			$this->_projectSourceOnCanvas($source, $canvas);
	
			// Enable progressive jpegs
			imageinterlace($canvas, true); 
			
			$outputImage = $this->_renderToImageData($canvas);
			imagedestroy($canvas);
		}

		$output = array(
			'resource' => $outputImage,
			'mime' => $this->params['mime'],
			'timestamp' => time()
		);
		imagedestroy($source);

		return $output;
	}
	
	protected function _isSourceEqualToTarget(array $scaleParams) {
		return 
			$this->params['w'] == $this->params['sourceWidth'] &&
			$this->params['h'] == $this->params['sourceHeight'] &&
			!$this->_isFilterDefined($scaleParams)
		;
	}
	
	protected function _isFilterDefined(array $scaleParams) {
		return 
			array_key_exists('filter', $scaleParams) &&
			$scaleParams['filter']
		;
	}

	/**
	 * Fetches the scaling parameters for this template.
	 * @param String $template Name of this template
	 **/
	public function getTemplateParameters($template) {
		if (count(self::$_config->template->{$template})) {
			$tplConfig = self::$_config->template->{$template}->toArray();
			return $tplConfig;
		} else throw new Exception('The template "'.$template.'" is not configured.');
	}


	/**
	 * Fetches existing template names.
	 * @return Array Numeric array, containing existing template names.
	 **/
	public function getTemplateNames() {
		return array_keys(self::$_config->template->toArray());
	}
	
	
	/**
	* Generate versions of an image file that are scaled according to the configured scaling templates.
	* @param String $filename The filename of the image to be scaled
	* @param Int $id The database ID of the image record
	* @param Boolean $overwrite Whether to overwrite existing files
	**/
	public function generateTemplateScaledImages($filename, $id, $overwrite = false) {
		if ($filename && $id) {
			$templates = $this->getTemplateNames();

			foreach ($templates as $t) {
				try {
					$this->scaleAndStore($filename, $id, $t, $overwrite);
				} catch(Exception $e) {
					throw new Exception("Error scaling ".$filename." (#".$id."): ".$e->getMessage());
				}
			}
		} else throw new Exception('A filename and id were not provided. Filename ['.$filename.'] Id ['.$id.']');
	}


	/** @return String The filename (which is the id), preceded by the scaled/template folder. Does not include basepath, upload folder and such. **/
	static public function getScaledPath($imageIdOrRecord, $template) {
		$id = $imageIdOrRecord instanceof Garp_Db_Table_Row ?
			$imageIdOrRecord->id :
			$imageIdOrRecord
		;

		return self::SCALED_FOLDER.'/'.$template.'/'.$id;
	}


	static public function getScaledUrl($imageIdOrRecord, $template) {
		$file = new Garp_Image_File();
		return $file->getUrl(self::getScaledPath($imageIdOrRecord, $template));
	}


	/**
	 * Scales an image according to an image template, and stores it.
	 * 
	 * @param String $filename Filename of the source image
	 * @param Int $id Id of the database record corresponding to this image file
	 * @param String $template Name of the template, if left empty, scaled versions for all templates will be generated.
	 **/
	public function scaleAndStore($filename, $id, $template = null, $overwrite = false) {
		$templates = !is_null($template) ?
			(array)$template :
			//	template is left empty; scale source file along all configured templates
			$templates = $this->getTemplateNames()
		;

		$file 		= new Garp_Image_File('upload');
		$sourceData = $file->fetch($filename);
		$imageType 	= $file->getImageType($filename);


		foreach ($templates as $template) {
			$this->_scaleAndStoreForTemplate($sourceData, $imageType, $id, $template, $overwrite);
		}
	}
	
	protected function _scaleAndStoreForTemplate($sourceData, $imageType, $id, $template, $overwrite) {
		$file 			= new Garp_Image_File('upload');
		$scaleParams 	= $this->getTemplateParameters($template);
		// clone this scaler, since scaling parameters are stored as class properties
		$clonedScaler	= clone($this);

		$scaledImageDataArray = $clonedScaler->scale(
			$sourceData,
			$scaleParams,
			$imageType
		);

		$scaledFilePath = $this->getScaledPath($id, $template);

		if ($overwrite || !$file->exists($scaledFilePath)) {
			$file->store($scaledFilePath, $scaledImageDataArray['resource'], true, false);
		}
	}

	/**
	 * Makes sure only allowed parameters are accepted, and merges them with the $params property.
	 * @param	Array	$params		Associative array containing image manipulation parameters
	 * @return	Void
	 **/
	protected function _setInputParams(Array &$params) {
		foreach ($params as $paramKey => $paramValue) {
			if (
				in_array($paramKey, $this->args, true) &&
				!is_null($paramValue)
			) {
				$this->inputParams[$paramKey] = $paramValue;
			}
		}

		$this->params = array_merge($this->params, $this->inputParams);
	}


	protected function _loadIniDefaults() {
		if (!self::$_config) {
			$ini = Zend_Registry::get('config');
			self::$_config = $ini->image;
		}

		$this->params['bgcolor'] = self::$_config->bgcolor;
	}


	/**
	 * Analyzes the source image and stores gained information like mime type / image type, width and height.
	 * @return Void
	 **/
	protected function _analyzeSourceImage($imageResource, $imageType) {
		$this->params['sourceWidth'] = imagesx($imageResource);
		$this->params['sourceHeight'] = imagesy($imageResource);
		$this->params['mime'] = image_type_to_mime_type($imageType);
		$this->params['type'] = $imageType;
	}


	/**
	 * If one of the canvas dimensions is omitted, calculate it and complement it in $this->params
	 * Requirement: Needs sourceWidth and sourceHeight to be present, so can only be called after analyzing the image.
	 * @return Void
	 **/
	private function _addOmittedCanvasDimension() {
		$sourceWidth = $this->params['sourceWidth'];
		$sourceHeight = $this->params['sourceHeight'];
		$sourceRatio = $sourceWidth / $sourceHeight;

		if (
			empty($this->params['w']) &&
			empty($this->params['h'])
		) {
			$this->params['w'] = $sourceWidth;
			$this->params['h'] = $sourceHeight;
		} elseif (empty($this->params['h'])) {
			if (
				!$this->params['grow'] &&
				$this->params['w'] > $sourceWidth
			) {
				$this->params['h'] = $sourceHeight;
			} else {
				$this->params['h'] = $this->params['w'] / $sourceRatio;
			}
		} elseif (empty($this->params['w'])) {
			if (
				!$this->params['grow'] &&
				$this->params['h'] > $sourceHeight
			) {
				$this->params['w'] = $sourceWidth;
			} else {
				$this->params['w'] = $this->params['h'] * $sourceRatio;
			}
		}
	}
	
	
	private function _createCanvasImage($imageType) {
		switch ($imageType) {
			case IMAGETYPE_GIF:
				$canvas = imageCreate($this->params['w'], $this->params['h']);
			break;
			case IMAGETYPE_JPEG:
			case IMAGETYPE_PNG:
				$canvas = imageCreateTrueColor($this->params['w'], $this->params['h']);
			break;
			default:
				throw new Exception('Sorry, this image type is not supported');
		}

		$this->_paintCanvas($canvas);
		return $canvas;
	}
	
	
	/**
	 * Fills the canvas with the provided background color.
	 * @param Resource $image
	 * @return Void
	 **/
	private function _paintCanvas(&$image) {
		if ($this->params['type'] === IMAGETYPE_JPEG) {
			if (
				!$this->params['crop'] ||
				!$this->params['grow']
			) {
				$this->_paintCanvasOpaque($image);
			}
		} else {
			$this->_paintCanvasTransparent($image);
		}
	}


	protected function _paintCanvasTransparent(&$image) {
		$transparency_index = imagecolortransparent($image);
		// If we have a specific transparent color
		if ($transparency_index >= 0) {
    		// Get the original image's transparent color's RGB values
		    $transparency_color = imagecolorsforindex($image, $transparency_index);
        	// Allocate the same color in the new image resource
		    $transparency_index = imagecolorallocate($image, $transparency_color['red'], $transparency_color['green'], $transparency_color['blue']);
	        // Completely fill the background of the new image with allocated color.
			imagefill($image, 0, 0, $transparency_index);
			// Set the background color for new image to transparent
			imagecolortransparent($image, $transparency_index);
		} elseif ($this->params['type'] === IMAGETYPE_PNG) {
			// Always make a transparent background color for PNGs that don't have one allocated already
			// Turn off transparency blending (temporarily)
			imagealphablending($image, false);
			// Create a new transparent color for image
			$color = imagecolorallocatealpha($image, 0, 0, 0, 127);
			// Completely fill the background of the new image with allocated color.
			imagefill($image, 0, 0, $color);
			// Restore transparency blending
			imagesavealpha($image, true);
		}
	}
	
	
	protected function _paintCanvasOpaque(&$image) {
		$red	= '00';
		$green	= '00';
		$blue	= '00';
		sscanf($this->params['bgcolor'], "%2x%2x%2x", $red, $green, $blue);
		$color = imageColorAllocate($image, $red, $green, $blue);
		imageFill($image, 0, 0, $color);
	}


	/**
	 * Writes the graphical output to image file data.
	 * @param Resource	$canvas
	 * @return Resource					Scaled image data
	 **/
	private function _renderToImageData(&$canvas) {
		ob_start();

		switch ($this->params['type']) {
			case IMAGETYPE_GIF:
				imagegif($canvas);
			break;
			case IMAGETYPE_JPEG:
				imagejpeg($canvas, null, $this->params['quality']);
			break;
			case IMAGETYPE_PNG:
				//	calculate PNG quality, because it runs from 0 (uncompressed) to 9, instead of 0 - 100.
				$pngQuality = ($this->params['quality'] - 100) / 11.111111;
				$pngQuality = round(abs($pngQuality));
				imagepng($canvas, null, $pngQuality, NULL);
			break;
			default:
				throw new Exception('Sorry, this image type is not supported');
		}

		$imgData = ob_get_contents();
		ob_end_clean();
		return $imgData;
	}


	/**
	 * Performs the actual projection of the source onto the canvas.
	 * @param Resource $source
	 * @param Resource $canvas
	 * @param Int $projectionWidth Width of the projection in pixels
	 * @param Int $projectionHeight Height of the projection in pixels
	 * @return Void
	 **/
	private function _projectSourceOnCanvas(&$source, &$canvas) {
		$srcX = 0;
		$srcY = 0;
		list($projectionWidth, $projectionHeight) = $this->_getProjectionSize();
		list($destX, $destY) = $this->_getLeftUpperCoordinateOnCanvas($projectionWidth, $projectionHeight);
		imagecopyresampled($canvas, $source, $destX, $destY, $srcX, $srcY, $projectionWidth, $projectionHeight, $this->params['sourceWidth'], $this->params['sourceHeight']);
	}


	/**
	 * Calculates the coordinates of the projection location on the canvas
	 * @param Int		$projectionWidth	Width of the projection
	 * @param Int		$projectionHeight	Height of the projection
	 * @return Array						Numeric array, containing x- and y-coordinates of the upper left point of the projection on the canvas
	 **/
	private function _getLeftUpperCoordinateOnCanvas($projectionWidth, $projectionHeight) {
		$projectionRatio = $projectionWidth / $projectionHeight;
		$canvasWidth = $this->params['w'];
		$canvasHeight = $this->params['h'];
		$canvasRatio = $canvasWidth / $canvasHeight;

		//	always center the projection horizontally
		if ($projectionWidth > $canvasWidth) {
			$x = - (($projectionWidth / 2) - ($this->params['w'] / 2));
		} else {
			$x = ($this->params['w'] - $projectionWidth) / 2;
		}

		switch ($this->params['cropfocus']) {
			case 'face':
				//	if the image is taller than the canvas, move the starting point halfway up the center
				if ($projectionHeight > $canvasHeight) {
					$y = - ((($projectionHeight / 2) - ($this->params['h'] / 2)) / 2);
				} else {
					$y = ($this->params['h'] - $projectionHeight) / 2;
				}
			break;
			case 'center':
			default:
				//	center the projection vertically
				if ($projectionHeight > $canvasHeight) {
					$y = - (($projectionHeight / 2) - ($this->params['h'] / 2));
				} else {
					$y = ($this->params['h'] - $projectionHeight) / 2;
				}
		} 

		return array($x, $y);
	}


	/**
	 * Calculate projection size of source image on canvas. The resulting projection might be larger than the canvas; this function does not consider cutoff by means of cropping.
	 * @return Array $projectionSize Numeric array containing projection width and height in pixels.
	 **/
	private function _getProjectionSize() {
		$sourceWidth = $this->params['sourceWidth'];
		$sourceHeight = $this->params['sourceHeight'];
		$sourceRatio = $sourceWidth / $sourceHeight;

		$canvasWidth = $this->params['w'];
		$canvasHeight = $this->params['h'];
		$canvasRatio = $canvasWidth / $canvasHeight;


		//	the image is not allowed to be cut off in any dimension
		if ($sourceRatio < $canvasRatio) {
			//	source is less landscape-like than canvas
			$leadDimension = !$this->params['crop'] ?
				'Height' : 'Width';
		} else {
			//	source is more landscape-like than canvas
			$leadDimension = !$this->params['crop'] ?
				'Width' : 'Height';
		}

		if (
			!$this->params['grow'] &&
			${'source'.$leadDimension} < ${'canvas'.$leadDimension}
		) {
			${'projection'.$leadDimension} = ${'source'.$leadDimension};
		} else {
			${'projection'.$leadDimension} = ${'canvas'.$leadDimension};
		}

		if (isset($projectionWidth)) {
			$projectionHeight = $projectionWidth / $sourceRatio;
		} elseif (isset($projectionHeight)) {
			$projectionWidth = $projectionHeight * $sourceRatio;
		}

		return array(round($projectionWidth), round($projectionHeight));
	}


}
