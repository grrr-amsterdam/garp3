<?php
/**
 * Storage and retrieval of user uploads, either locally or on an external CDN.
 * @author David Spreekmeester | Grrr.nl
 * @package Garp
 */
class Garp_File {
	protected $_storageTypes = array('local', 's3');
	
	protected $_requiredConfigParams = array('type', 'domain', 'path', 'extensions');
	
	protected $_requiredConfigPaths = array('upload', 'static');

	protected $_allowedTypes = array('image', 'document');

	protected $_defaultUploadType = 'document';

	/** @var Garp_File_Storage_Protocol $_storage An Garp_File_Storage_Protocol compliant object, such as Garp_File_Storage_S3. */
	protected $_storage;

	protected $_uploadOrStatic = 'upload';
	
	const SEPERATOR = '-';
	
	protected $_path;



	/**
	* @param String $uploadType Options: 'documents' or 'images'. Documents are all files besides images.
	* @param Boolean $uploadOrStatic Options: 'upload' or 'static'. Whether this upload is a user upload, stored in the uploads directory, or a static file used in the site.
	*/
	public function __construct($uploadType = null, $uploadOrStatic = null) {
		$ini = $this->_getIni();
		$this->_validateConfig($ini);
		$this->_validateUploadType($uploadType);
		$this->_validateUploadOrStatic($uploadOrStatic);

		if (!is_null($uploadOrStatic))
			$this->_uploadOrStatic = $uploadOrStatic;

		$this->_path = $this->_getPath($ini, $uploadType);
		$this->_initStorage($ini);
	}
	

	/** Make public methods of the Garp_File_Storage object available. */
	public function __call($method, $args) {
		if (method_exists($this->_storage, $method)) {
			if ($method == 'store') {
				$filename = $args[0];
				if (
					!array_key_exists(3, $args) ||
					$args[3]
				) {
					//	$this->_storage->store()'s $formatFilename argument is true
					$this->_restrictExtension($filename);
				}
			}

			return call_user_func_array(array($this->_storage, $method), $args);
		}
	}
	

	public static function formatFilename($filename) {
		if (strpos($filename, '/') === false) {
			$filename = strtolower($filename);
			$plainFilename = preg_replace(
				array(
					'/[_ ]/',
					'/[^\da-zA-Z\.'.self::SEPERATOR.']/'
				),
				array(
					self::SEPERATOR,
					''
				),
				$filename
			);
			return trim($plainFilename, self::SEPERATOR);
		} else throw new Exception(__FUNCTION__.'() is not for paths, please stick to filenames.');
	}


	/** Returns a filename with the next follow-up-number. F.i.: cookie.jpg -> cookie-2.jpg, cookie-15.jpg -> cookie-16.jpg */
	public static function getCumulativeFilename($filename) {
		$filename = self::formatFilename($filename);

		$filenameParts = explode('.', $filename);
		$ext = array_pop($filenameParts);
		$base = implode('.', $filenameParts);
		$base = preg_match('/'.self::SEPERATOR.'\d+$/', $base) ?
			preg_replace_callback(
				'/'.self::SEPERATOR.'(\d+)$/',
				function($matches) {
					return Garp_File::SEPERATOR.++$matches[1];
				},
				$base) :
			$base.self::SEPERATOR.'2'
		;

		return $base.'.'.$ext;
	}


	protected function _getExtension($filename) {
		$filenameParts = explode('.', $filename);
		if (count($filenameParts) >1) {
			return $filenameParts[count($filenameParts) -1];
		} else throw new Exception("The provided filename does not have an extension. Please use the appropriate 3-character extension (such as .jpg, .png) after your filename.");
	}


	protected function _getPath($ini, $uploadType) {
		return !$uploadType ?
			$ini->cdn->path->{$this->_uploadOrStatic}->{$this->_defaultUploadType} :
			$ini->cdn->path->{$this->_uploadOrStatic}->{$uploadType}
		;
	}


	protected function _validateConfig($ini) {
		if (isset($ini->cdn)) {
			foreach ($this->_requiredConfigParams as $param) {
				if (
					(
						!isset($ini->cdn->{$param}) ||
						!$ini->cdn->{$param}
					) &&
					!(
						//	Don't break on CLI
						$param === 'domain' &&
						defined('HTTP_HOST')
					)
				) {
					throw new Exception("'cdn.{$param}' was not set in application.ini.");
				}
			}
			$configuredCdnType = strtolower($ini->cdn->type);
			if (in_array($configuredCdnType, $this->_storageTypes)) {
				foreach ($this->_requiredConfigPaths as $uploadOrStatic) {
					foreach ($this->_allowedTypes as $type) {
						if (
							!isset($ini->cdn->path->{$uploadOrStatic}->{$type}) ||
							!$ini->cdn->path->{$uploadOrStatic}->{$type}
						) {
							throw new Exception("The required cdn.path.{$uploadOrStatic}.{$type} was not set in application.ini.");
						}
					}
				}
			} else throw new Exception("'{$ini->cdn->type}' is not a valid CDN type. Try: ".implode(" or ", $this->_storageTypes).'.');
		} else throw new Exception("The 'cdn' variable is not set in application.ini.");
	}
	
	
	protected function _validateUploadType($uploadType) {
		if (
			!is_null($uploadType) &&
			!in_array($uploadType, $this->_allowedTypes)
		) {
			throw new Exception("'{$uploadType}' is not a valid upload type. Try: '".implode("' or '", $this->_allowedTypes)."'.");
		}
	}


	protected function _validateUploadOrStatic($uploadOrStatic) {
		if (
			$uploadOrStatic !== 'upload' &&
			$uploadOrStatic !== 'static' &&
			!is_null($uploadOrStatic)
		)
			throw new Exception("The 'uploadOrStatic' variable should be either 'upload' or 'static' (dOh!) - so not '{$uploadOrStatic}'");
	}


	protected function _initStorage($ini) {
		switch ($ini->cdn->type) {
			case 's3':
				$this->_storage = new Garp_File_Storage_S3($ini->cdn, $this->_path);
			break;
			case 'local':
				$this->_storage = new Garp_File_Storage_Local($ini->cdn, $this->_path);
			break;
			default:
				throw new Exception("The '{$ini->cdn->type}' protocol is not yet implemented.");
		}
	}


	protected function _restrictExtension($filename) {
		if ($filename) {
			$extension = $this->_getExtension($filename);
			$allowedExtensions = $this->_getAllowedExtensions();
			if (!in_array(strtolower($extension), $allowedExtensions)) {
				throw new Exception("The file type you're trying to upload is not allowed. Try: ".$this->_humanList($allowedExtensions, null, 'or'));
			}
		} else throw new Exception("The filename was empty.");
	}
	
	protected function _getAllowedExtensions() {
		$ini = $this->_getIni();		
		return explode(',', $ini->cdn->extensions);
	}
	
	
	protected function _getIni() {
		return Garp_Cache_Ini::factory(APPLICATION_PATH.'/configs/application.ini');
	}
	
	
	/**
	 * @param Array $list Numeric Array of String elements,
	 * @param String $decorator Element decorator, f.i. a quote.
	 * @param String $lastItemSeperator Seperates the last item from the rest instead of a comma, for instance: 'and' or 'or'.
	 * @return String Listed elements, like "Snoop, Dre and Devin".
	 */
	protected function _humanList(Array $list, $decorator = null, $lastItemSeperator = 'and') {
		$listCount = count($list);
		if ($listCount === 1) {
			return $decorator.current($list).$decorator;
		} elseif ($listCount === 2) {
			return $decorator.implode($decorator." {$lastItemSeperator} ".$decorator, $list).$decorator;
		} elseif ($listCount > 2) {
			$last = array_pop($list);
			return $decorator.implode($decorator.", ".$decorator, $list).$decorator." {$lastItemSeperator} ".$decorator.$last.$decorator;
		}
	}
}