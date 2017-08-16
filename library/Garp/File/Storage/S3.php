<?php
use Garp\Functional as f;

/**
 * Storage and retrieval of user uploads, from Amazon's S3 CDN.
 *
 * @package Garp_File_Storage
 * @author  David Spreekmeester <david@grrr.nl>
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_File_Storage_S3 implements Garp_File_Storage_Protocol {
    /**
     * Store the configuration so that it is built just once per session.
     *
     * @var bool
     */
    protected $_config = array();

    protected $_requiredS3ConfigParams = array('apikey', 'secret', 'bucket');

    /**
     * @var Zend_Service_Amazon_S3 $_api
     */
    protected $_api;

    protected $_apiInitialized = false;

    protected $_bucketExists = false;

    protected $_knownMimeTypes = array(
        'js' => 'text/javascript',
        'css' => 'text/css',
        'html' => 'text/html',
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml'
    );

    /**
     * Number of seconds after which to timeout the S3 action.
     * Should support uploading large (20mb) files.
     *
     * @var int
     */
    const TIMEOUT = 400;

    /**
     * @param array  $config    CDN configuration
     * @param string $path      Relative path to the location of the stored file,
     *                          excluding trailing slash but always preceded by one.
     * @param bool   $keepalive Wether to keep the socket open
     * @return void
     */
    public function __construct(array $config, $path = '/', $keepalive = false) {
        $this->_setConfigParams($config);

        if ($path) {
            $this->_config['path'] = $path;
        }

        $this->_config['keepalive'] = $keepalive;
    }

    public function setPath($path) {
        $this->_config['path'] = $path;
    }

    public function exists($filename) {
        $this->_initApi();
        return $this->_api->isObjectAvailable($this->_config['bucket'] . $this->_getUri($filename));
    }

    /**
     * Fetches the url to the file, suitable for public access on the web.
     *
     * @param string $filename
     * @return string
     */
    public function getUrl($filename) {
        $this->_verifyPath();
        return new Garp_Util_AssetUrl($this->_config['path'] . '/' . $filename);
    }

    /**
     * Fetches the file data.
     *
     * @param string $filename
     * @return string
     */
    public function fetch($filename) {
        $this->_initApi();
        $obj = $this->_api->getObject($this->_config['bucket'] . $this->_getUri($filename));
        if ($this->_config['gzip'] && $this->_gzipIsAllowedForFilename($filename)) {
            $unzipper = new Garp_File_Unzipper($obj);
            $obj = $unzipper->getUnpacked();
        }
        return $obj;
    }

    /**
     * Lists all files in the upload directory.
     *
     * @return array
     */
    public function getList() {
        $this->_initApi();
        $this->_verifyPath();

        // strip off preceding slash, add trailing one.
        $path = substr($this->_config['path'], 1) . '/';
        $objects = $this->_api->getObjectsByBucket(
            $this->_config['bucket'], array('prefix' => $path)
        );

        return $objects;
    }

    /**
     * Returns mime type of given file.
     *
     * @param string $filename
     * @return string
     */
    public function getMime($filename) {
        $this->_initApi();
        $info = $this->_api->getInfo($this->_config['bucket'] . $this->_getUri($filename));

        if (array_key_exists('type', $info)) {
            return $info['type'];
        }
        throw new Exception("Could not retrieve mime type of {$filename}.");
    }

    public function getSize($filename) {
        $this->_initApi();
        $info = $this->_api->getInfo($this->_config['bucket'] . $this->_getUri($filename));

        if (array_key_exists('size', $info)
            && is_numeric($info['size'])
        ) {
            return $info['size'];
        }
        throw new Exception("Could not retrieve size of {$filename}.");
    }

    public function getEtag($filename) {
        $this->_initApi();
        $path = $this->_config['bucket'] . $this->_getUri($filename);
        $info = $this->_api->getInfo($path);

        if (array_key_exists('etag', $info)) {
            $info['etag'] = str_replace('"', '', $info['etag']);
            return $info['etag'];
        }
        throw new Exception("Could not retrieve eTag of {$filename}.");
    }

    /**
     * Returns last modified time of file, as a Unix timestamp.
     *
     * @param string $filename
     * @return string
     */
    public function getTimestamp($filename) {
        $this->_initApi();
        $info = $this->_api->getInfo($this->_config['bucket'] . $this->_getUri($filename));

        if (array_key_exists('mtime', $info)) {
            return $info['mtime'];
        }
        throw new Exception("Could not retrieve timestamp of {$filename}.");
    }

    /**
     * @param string $filename
     * @param string $data           Binary file data
     * @param bool   $overwrite      Whether to overwrite this file, or create a unique name
     * @param bool   $formatFilename Whether to correct the filename,
     *                               f.i. ensuring lowercase characters.
     * @return string                Destination filename.
     */
    public function store($filename, $data, $overwrite = false, $formatFilename = true) {
        $this->_initApi();
        $this->_createBucketIfNecessary();

        if ($formatFilename) {
            $filename = Garp_File::formatFilename($filename);
        }

        if (!$overwrite) {
            while ($this->exists($filename)) {
                $filename = Garp_File::getCumulativeFilename($filename);
            }
        }

        $path = $this->_config['bucket'] . $this->_getUri($filename);
        $meta = array(
            Zend_Service_Amazon_S3::S3_ACL_HEADER => Zend_Service_Amazon_S3::S3_ACL_PUBLIC_READ,
        );

        if (false !== strpos($filename, '.')) {
            $ext = substr($filename, strrpos($filename, '.')+1);
            if (array_key_exists($ext, $this->_knownMimeTypes)) {
                $mime = $this->_knownMimeTypes[$ext];
            } else {
                $finfo = new finfo(FILEINFO_MIME);
                $mime  = $finfo->buffer($data);
            }
        } else {
            $finfo = new finfo(FILEINFO_MIME);
            $mime  = $finfo->buffer($data);
        }
        $meta[Zend_Service_Amazon_S3::S3_CONTENT_TYPE_HEADER] = $mime;
        if ($this->_config['gzip'] && $this->_gzipIsAllowedForFilename($filename)) {
            $meta['Content-Encoding'] = 'gzip';
            $data = gzencode($data);
        }

        $success = $this->_api->putObject(
            $path,
            $data,
            $meta
        );
        return $success ? $filename : false;
    }

    public function remove($filename) {
        $this->_initApi();
        return $this->_api->removeObject($this->_config['bucket'] . $this->_getUri($filename));
    }

    /**
     * Returns the uri for internal Zend_Service_Amazon_S3 use.
     *
     * @param string $filename
     * @return string
     */
    protected function _getUri($filename) {
        $this->_verifyPath();
        $path = $this->_config['path'];

        return $path .
            ($path[strlen($path)-1] === '/' ? null : '/') .
            $filename;
    }

    protected function _createBucketIfNecessary() {
        if (!$this->_bucketExists) {
            if (!$this->_api->isBucketAvailable($this->_config['bucket'])) {
                $this->_api->createBucket($this->_config['bucket']);
            }

            $this->_bucketExists = true;
        }
    }

    protected function _validateConfig(array $config) {
        $missingProps = f\filter(
            f\not(f\prop_of($config)),
            $this->_requiredS3ConfigParams
        );
        if (count($missingProps)) {
            throw new Exception(
                sprintf('Missing required option(s): %s', implode(',', $missingProps))
            );
        }
    }

    protected function _setConfigParams(array $config) {
        if (!$this->_config) {
            $this->_validateConfig($config);

            $this->_config['apikey'] = f\prop('apikey', $config);
            $this->_config['secret'] = f\prop('secret', $config);
            $this->_config['bucket'] = f\prop('bucket', $config);
            $this->_config['gzip']   = f\prop('gzip', $config);
            $this->_config['gzip_exceptions'] = (array)f\prop('gzip_exceptions', $config);
        }
    }


    protected function _initApi() {
        if (!$this->_apiInitialized) {
            @ini_set('max_execution_time', self::TIMEOUT);
            @set_time_limit(self::TIMEOUT);
            if (!$this->_api) {
                $this->_api = new Garp_Service_Amazon_S3(
                    $this->_config['apikey'],
                    $this->_config['secret']
                );
            }

            $this->_api->getHttpClient()->setConfig(
                array(
                    'timeout' => self::TIMEOUT,
                    'keepalive' => $this->_config['keepalive']
                )
            );

            $this->_apiInitialized = true;
        }
    }


    protected function _verifyPath() {
        if (!$this->_config['path']) {
            throw new Exception("There is not path configured, please do this with setPath().");
        }
    }

    protected function _gzipIsAllowedForFilename($filename) {
        $ext = substr($filename, strrpos($filename, '.')+1);
        if (!$ext) {
            return true;
        }
        return !in_array($ext, $this->_getGzipExceptions());
    }

    protected function _getGzipExceptions() {
        return $this->_config['gzip_exceptions'];
    }

}
