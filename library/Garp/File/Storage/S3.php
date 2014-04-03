<?php
/**
 * Storage and retrieval of user uploads, from Amazon's S3 CDN.
 * @author David Spreekmeester | Grrr.nl
 * @package Garp
 */
class Garp_File_Storage_S3 implements Garp_File_Storage_Protocol {
	protected $_apikey;
	
	protected $_secret;
	
	protected $_bucket;
	
	protected $_domain;
	
	protected $_path;
	
	protected $_requiredS3ConfigParams = array('apikey', 'secret', 'bucket');
	
	/** @var Zend_Service_Amazon_S3 $_api */
	protected $_api;

	/** @const Int TIMEOUT Number of seconds after which to timeout the S3 action. Should support uploading large (20mb) files. */
	const TIMEOUT = 400;



	public function __construct(Zend_Config $config, $path) {
		$this->_validateConfig($config, $path);
		$this->_setConfigParams($config, $path);
	}


	public function exists($filename) {
		$this->_initApi();
		return $this->_api->isObjectAvailable($this->_getUri($filename));
	}


	/** Fetches the url to the file, suitable for public access on the web. */
	public function getUrl($filename) {
		return 'http://'.$this->_domain.$this->_path.'/'.$filename;
	}


	/** Fetches the file data. */
	public function fetch($filename) {
		// return fopen($this->getUrl($filename), 'r');
		$this->_initApi();
		return $this->_api->getObject($this->_getUri($filename));
	}


	/** Lists all files in the upload directory. */
	public function getList() {
		$this->_initApi();
		return $this->_api->getObjectsByBucket($this->_bucket);
	}


	/** Returns mime type of given file. */
	public function getMime($filename) {
		$this->_initApi();
		$info = $this->_api->getInfo($this->_getUri($filename));

		if (array_key_exists('type', $info)) {
			return $info['type'];
		} else throw new Exception("Could not retrieve mime type of {$filename}.");
	}
	
	
	public function getSize($filename) {
		$this->_initApi();
		$info = $this->_api->getInfo($this->_getUri($filename));

		if (
			array_key_exists('size', $info) &&
			is_numeric($info['size'])
		) {
			return $info['size'];
		} else throw new Exception("Could not retrieve size of {$filename}.");
	}


	/** Returns last modified time of file, as a Unix timestamp. */
	public function getTimestamp($filename) {
		$this->_initApi();
		$info = $this->_api->getInfo($this->_getUri($filename));

		if (array_key_exists('mtime', $info)) {
			return $info['mtime'];
		} else throw new Exception("Could not retrieve timestamp of {$filename}.");
	}


	/**
	* @param String $filename
	* @param String $data Binary file data
	* @param Boolean $overwrite Whether to overwrite this file, or create a unique name
	* @param Boolean $formatFilename Whether to correct the filename, f.i. ensuring lowercase characters.
	* @return String Destination filename.
	*/
	public function store($filename, $data, $overwrite = false, $formatFilename = true) {
		$this->_initApi();
		$this->_createBucketIfNecessary();

		if ($formatFilename)
			$filename = Garp_File::formatFilename($filename);

		if (!$overwrite) {
			while ($this->exists($filename)) {
				$filename = Garp_File::getCumulativeFilename($filename);
			}
		}

		if ($this->_api->putObject(
			$this->_getUri($filename),
			$data,
			array(
				Zend_Service_Amazon_S3::S3_ACL_HEADER => Zend_Service_Amazon_S3::S3_ACL_PUBLIC_READ
			)
		)) {
			return $filename;
		} else return false;
	}


	public function remove($filename) {
		$this->_initApi();
		return $this->_api->removeObject($this->_getUri($filename));
	}


	/** Returns the uri for internal Zend_Service_Amazon_S3 use. */
	protected function _getUri($filename) {
		return $this->_bucket.$this->_path.'/'.$filename;
	}


	protected function _createBucketIfNecessary() {
		if (!$this->_api->isBucketAvailable($this->_bucket)) {
			$this->_api->createBucket($this->_bucket);
		}
	}


	protected function _validateConfig(Zend_Config $config, $path) {
		foreach ($this->_requiredS3ConfigParams as $reqPar)
			if (!$config->s3->{$reqPar})
				throw new Exception("'cdn.s3.{$reqPar}' must be set in application.ini.");

		if (!$path)
			throw new Exception("Did not receive a valid path to store uploads.");
	}
	
	
	protected function _setConfigParams(Zend_Config $config, $path) {
		foreach ($config as $paramName => $paramValue) {
			if ($paramName !== 's3') {
				if (property_exists($this, '_'.$paramName))
					$this->{'_'.$paramName} = $paramValue;
			}
		}
		foreach ($config->s3 as $paramName => $paramValue) {
			if (property_exists($this, '_'.$paramName)) {
				$this->{'_'.$paramName} = $paramValue;
			} else throw new Exception("cdn.s3.{$paramName} is an invalid parameter.");
		}
		
		$this->_path = $path;
	}
	
	
	protected function _initApi() {
		@ini_set('max_execution_time', self::TIMEOUT);
		@set_time_limit(self::TIMEOUT);
		if (!$this->_api) {
			$this->_api = new Zend_Service_Amazon_S3(
				$this->_apikey,
				$this->_secret
			);
		}
		
		$this->_api->getHttpClient()->setConfig(array('timeout' => self::TIMEOUT));
	}
}