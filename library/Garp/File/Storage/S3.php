<?php
use Aws\Credentials\Credentials;
use Garp\Functional as f;
use GuzzleHttp\Promise;
use Aws\S3\S3Client;

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
    protected $_config = [];

    protected $_requiredS3ConfigParams = ['apikey', 'secret', 'bucket', 'region'];

    /**
     * @var Aws\S3\S3Client
     */
    protected $_api;

    protected $_knownMimeTypes = [
        'js' => 'text/javascript',
        'css' => 'text/css',
        'html' => 'text/html',
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml'
    ];

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

    public function exists($filename): bool {
        return $this->_getApi()->doesObjectExist(
            $this->_config['bucket'],
            $this->_getUri($filename)
        );
    }

    /**
     * Fetches the url to the file, suitable for public access on the web.
     *
     * @param string $filename
     * @return string
     */
    public function getUrl($filename): Garp_Util_AssetUrl {
        $this->_verifyPath();
        return new Garp_Util_AssetUrl($this->_config['path'] . '/' . $filename);
    }

    /**
     * Fetches the file data.
     *
     * @param string $filename
     * @return string
     */
    public function fetch($filename): string {
        return $this->_getApi()->getObject([
            'Bucket' => $this->_config['bucket'],
            'Key' => $this->_getUri($filename),
        ])->get('Body');
    }

    /**
     * Lists all files in the upload directory.
     *
     * @return array
     */
    public function getList(): array {
        $this->_verifyPath();

        // strip off preceding slash, add trailing one.
        $path = substr($this->_config['path'], 1) . '/';
        return $this->_getApi()->listObjects([
            'Bucket' => $this->_config['bucket'],
            'Prefix' => $path
        ])->get('Contents');
    }

    /**
     * Returns mime type of given file.
     *
     * @param string $filename
     * @return string
     */
    public function getMime($filename): string {
        $info = $this->_getApi()->getObject([
            'Bucket' => $this->_config['bucket'],
            'Key' => $this->_getUri($filename),
        ]);

        if (f\prop('ContentType', $info)) {
            return $info['ContentType'];
        }
        throw new Exception("Could not retrieve mime type of {$filename}.");
    }

    public function getSize($filename): float {
        $info = $this->_getApi()->getObject([
            'Bucket' => $this->_config['bucket'],
            'Key' => $this->_getUri($filename),
        ]);

        if (f\prop('ContentLength', $info)) {
            return $info['ContentLength'];
        }
        throw new Exception("Could not retrieve size of {$filename}.");
    }

    public function getEtag($filename): string {
        $info = $this->_getApi()->getObject([
            'Bucket' => $this->_config['bucket'],
            'Key' => $this->_getUri($filename),
        ]);

        if (f\prop('ETag', $info)) {
            return str_replace('"', '', $info['ETag']);
        }
        throw new Exception("Could not retrieve eTag of {$filename}.");
    }

    /**
     * Returns last modified time of file, as a Unix timestamp.
     *
     * @param string $filename
     * @return string
     */
    public function getTimestamp($filename): string {
        $info = $this->_getApi()->getObject([
            'Bucket' => $this->_config['bucket'],
            'Key' => $this->_getUri($filename),
        ]);

        if (f\prop('LastModified', $info)) {
            return $info['LastModified'];
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
    public function store($filename, $data, $overwrite = false, $formatFilename = true): string {
        if ($formatFilename) {
            $filename = Garp_File::formatFilename($filename);
        }

        if (!$overwrite) {
            while ($this->exists($filename)) {
                $filename = Garp_File::getCumulativeFilename($filename);
            }
        }

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
        $awsPayload = [
            'Bucket' => $this->_config['bucket'],
            'Key' => $this->_getUri($filename),
            'Body' => $data,
            'ACL' => 'public-read',
            'ContentType' => $mime
        ];
        if ($this->_config['gzip'] && $this->_gzipIsAllowedForFilename($filename)) {
            $awsPayload['ContentEncoding'] = 'gzip';
            $awsPayload['Body'] = gzencode($awsPayload['Body']);
        }

        $success = $this->_getApi()->putObject($awsPayload);
        return $success ? $filename : '';
    }

    public function remove(string $filename) {
        return $this->_getApi()->deleteObject([
            'Bucket' => $this->_config['bucket'],
            'Key' =>  $this->_getUri($filename)
        ]);
    }

    /**
     * Returns the uri for internal Zend_Service_Amazon_S3 use.
     *
     * @param string $filename
     * @return string
     */
    protected function _getUri($filename): string {
        $this->_verifyPath();
        // Note: AWS SDK does not want the URI to start with a slash, as opposed to the old Zend
        // Framework implementation.
        $path = trim($this->_config['path'], '/');
        return $path . '/' . $filename;
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
            $this->_config['region'] = f\prop('region', $config);
            $this->_config['gzip']   = f\prop('gzip', $config);
            $this->_config['gzip_exceptions'] = (array)f\prop('gzip_exceptions', $config);
        }
    }

    protected function _getApi(): S3Client {
        if (!$this->_api) {
            @ini_set('max_execution_time', self::TIMEOUT);
            @set_time_limit(self::TIMEOUT);
            $this->_api = new S3Client([
                'region'      => $this->_config['region'],
                'version'     => 'latest',
                'credentials' => $this->_getAwsCredentialsProvider(),
                'http' => [
                    'timeout' => self::TIMEOUT
                ]
            ]);
        }
        return $this->_api;
    }

    protected function _getAwsCredentialsProvider(): callable {
        return function () {
            return Promise\promise_for(
                new Credentials($this->_config['apikey'], $this->_config['secret'])
            );
        };
    }

    protected function _verifyPath() {
        if (!$this->_config['path']) {
            throw new Exception("There is no path configured, please do this with setPath().");
        }
    }

    protected function _gzipIsAllowedForFilename($filename): bool {
        $ext = substr($filename, strrpos($filename, '.')+1);
        if (!$ext) {
            return true;
        }
        return !in_array($ext, $this->_getGzipExceptions());
    }

    protected function _getGzipExceptions(): array {
        return $this->_config['gzip_exceptions'];
    }

}
