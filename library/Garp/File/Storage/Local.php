<?php
/**
 * Storage and retrieval of user uploads, from the local web server.
 * @author David Spreekmeester | Grrr.nl
 * @package Garp
 */
class Garp_File_Storage_Local implements Garp_File_Storage_Protocol {
	protected $_docRoot;

	protected $_domain;
	
	protected $_path;
	
	const PERMISSIONS = 0774;



	public function __construct(Zend_Config $config, $path) {
		$this->_docRoot = APPLICATION_PATH."/../public";
		$this->_path = $path;
		$this->_domain = $config->domain;
	}


	public function setPath($path) {
		$this->_path = $path;
	}


	public function exists($filename) {
		return file_exists($this->_getFilePath($filename));
	}


	/** Fetches the url to the file, suitable for public access on the web. */
	public function getUrl($filename) {
		return 'http://'.$this->_domain.$this->_path.'/'.$filename;
	}
	

	/** Fetches the file data. */
	public function fetch($filename) {
		return file_get_contents($this->_getFilePath($filename));
	}

	/** Lists all valid files in the upload directory. */
	public function getList() {
		$list = array();
		$dir = $this->_docRoot.$this->_path;
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if (
						substr($file, 0, 1) !== '.' &&
						strpos($file, '.') !== false
					) {
						$list[] = $file;
					}
				}
				closedir($dh);
			}
		} else throw new Exception($dir.' is not a directory.');

		return $list;
	}

	/** Returns mime type of given file. */
	public function getMime($filename) {
		$imageInfo = @getimagesize($this->_getFilePath($filename));
		if (is_array($imageInfo))
			return $imageInfo['mime'];
		else throw new Exception("Could not retrieve the mime type for ".$this->_getFilePath($filename));
	}


	public function getSize($filename) {
		return filesize($this->_getFilePath($filename));
	}


	/** Returns last modified time of file, as a Unix timestamp. */
	public function getTimestamp($filename) {
		return filemtime($this->_getFilePath($filemtime));
	}


	/**
	* @param String $filename
	* @param String $data Binary file data
	* @param Boolean $overwrite Whether to overwrite this file, or create a unique name
	* @param Boolean $formatFilename Whether to correct the filename, f.i. ensuring lowercase characters.
	* @return String Destination filename.
	*/
	public function store($filename, $data, $overwrite = false, $formatFilename = true) {
		$this->_verifyDirectory($filename);
		if ($formatFilename)
			$filename = Garp_File::formatFilename($filename);

		if (!$overwrite) {
			while ($this->exists($filename)) {
				$filename = Garp_File::getCumulativeFilename($filename);
			}
		}

		if (file_put_contents($this->_getFilePath($filename), $data) !== false) {
			chmod($this->_getFilePath($filename), self::PERMISSIONS);
			return $filename;
		} else return false;
	}
	
	public function remove($filename) {
		return unlink($this->_getFilePath($filename));
	}
	

	/** Fetches the path to the file, suitable for storage and retrieval commands on disk. */	
	protected function _getFilePath($filename) {
		return $this->_docRoot.$this->_path.'/'.$filename;
	}
	
	
	protected function _verifyDirectory($filename) {
		if (!file_exists($this->_docRoot.$this->_path)) {
			if (!mkdir($this->_docRoot.$this->_path, self::PERMISSIONS, true)) {
				throw new Exception("Could not create directory ".$this->_docRoot.$this->_path);
			}
		}
		if (!is_writable($this->_docRoot.$this->_path)) {
			throw new Exception("Could not write to ".$this->_docRoot.$this->_path);
		}

		if (strpos($filename, '/') !== false) {
			$subdir = $this->_docRoot.$this->_path.'/'.dirname($filename);
			if (!file_exists($subdir)) {
				if (!mkdir($subdir, self::PERMISSIONS, true)) {
					throw new Exception("Could not create directory ".$subdir);
				}
			}
				
			if (!is_writable($subdir)) {
				throw new Exception("Could not write to ".$subdir);
			}
		}
	}
}
