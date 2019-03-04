<?php
use Garp\Functional as f;

/**
 * Storage and retrieval of user uploads, from the local web server.
 *
 * @package Garp_File_Storage
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_File_Storage_Local implements Garp_File_Storage_Protocol {
    protected $_docRoot;

    protected $_domain;

    protected $_path;

    protected $_ssl;

    protected $_gzip;

    const PERMISSIONS = 0774;

    public function __construct(array $config, $path) {
        $this->_docRoot = APPLICATION_PATH . "/../public";
        $this->_path = $path;
        $this->_domain = f\prop('domain', $config);
        $this->_ssl = !!f\prop('ssl', $config);
        $this->_gzip = f\prop('gzip', $config);
    }

    public function setDocRoot($docRoot) {
        $this->_docRoot = $docRoot;
    }

    public function getDocRoot() {
        return $this->_docRoot;
    }

    public function setPath($path) {
        $this->_path = $path;
    }

    public function exists($filename): bool {
        return file_exists($this->_getFilePath($filename));
    }

    /**
     * Fetches the url to the file, suitable for public access on the web.
     *
     * @param string $filename
     * @return Garp_Util_AssetUrl
     */
    public function getUrl($filename): Garp_Util_AssetUrl {
        return new Garp_Util_AssetUrl($this->_path . '/' . $filename);
    }

    /**
     * Fetches the file data.
     *
     * @param string $filename
     * @return string
     */
    public function fetch($filename): string {
        $data = file_get_contents($this->_getFilePath($filename));
        if ($this->_gzip) {
            $probablyGzipped = bin2hex(substr($data, 0, 2)) == '1f8b';
            $data = $probablyGzipped ? gzdecode($data) : $data;
        }
        return $data;
    }

    /**
     * Lists all valid files in the upload directory.
     *
     * @return array
     */
    public function getList(): array {
        $list = array();
        $dir = $this->_docRoot . $this->_path;
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if (substr($file, 0, 1) !== '.'
                        && strpos($file, '.') !== false
                    ) {
                        $list[] = $file;
                    }
                }
                closedir($dh);
            }
        } else {
            throw new Exception($dir . ' is not a directory.');
        }

        return $list;
    }

    /**
     * Returns mime type of given file.
     *
     * @param string $filename
     * @return string
     */
    public function getMime($filename): string {
        $imageInfo = @getimagesize($this->_getFilePath($filename));
        if (is_array($imageInfo)) {
            return $imageInfo['mime'];
        }
        throw new Exception(
            "Could not retrieve the mime type for " .
            $this->_getFilePath($filename)
        );
    }

    public function getSize($filename): float {
        return filesize($this->_getFilePath($filename));
    }

    /**
     * Returns last modified time of file, as a Unix timestamp.
     *
     * @param string $filename
     * @return int
     */
    public function getTimestamp($filename): string {
        return filemtime($this->_getFilePath($filemtime));
    }

    /**
     * @param string $filename
     * @param string $data Binary file data
     * @param bool $overwrite Whether to overwrite this file, or create a unique name
     * @param bool $formatFilename Whether to correct the filename,
     *                             f.i. ensuring lowercase characters.
     * @return string Destination filename.
     */
    public function store(
        $filename, $data, $overwrite = false, $formatFilename = true
    ): string {
        $this->_verifyDirectory($filename);
        if ($formatFilename) {
            $filename = Garp_File::formatFilename($filename);
        }

        if (!$overwrite) {
            while ($this->exists($filename)) {
                $filename = Garp_File::getCumulativeFilename($filename);
            }
        }

        if ($this->_gzip) {
            $data = gzencode($data);
        }

        if (file_put_contents($this->_getFilePath($filename), $data) !== false) {
            chmod($this->_getFilePath($filename), self::PERMISSIONS);
            return $filename;
        } else {
            return false;
        }
    }

    public function remove(string $filename) {
        return unlink($this->_getFilePath($filename));
    }

    /**
     * Fetches the path to the file, suitable for storage and retrieval commands on disk.
     *
     * @param string $filename
     * @return string
     */
    protected function _getFilePath($filename) {
        return $this->_docRoot . $this->_path . '/' . $filename;
    }

    protected function _verifyDirectory($filename) {
        if (!file_exists($this->_docRoot . $this->_path)) {
            if (!mkdir($this->_docRoot . $this->_path, self::PERMISSIONS, true)) {
                throw new Exception("Could not create directory " . $this->_docRoot . $this->_path);
            }
        }
        if (!is_writable($this->_docRoot . $this->_path)) {
            throw new Exception("Could not write to " . $this->_docRoot . $this->_path);
        }

        if (strpos($filename, '/') !== false) {
            $subdir = $this->_docRoot . $this->_path . '/' . dirname($filename);
            if (!file_exists($subdir)) {
                if (!mkdir($subdir, self::PERMISSIONS, true)) {
                    throw new Exception("Could not create directory " . $subdir);
                }
            }

            if (!is_writable($subdir)) {
                throw new Exception("Could not write to " . $subdir);
            }
        }
    }
}
