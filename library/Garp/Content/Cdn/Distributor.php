<?php
use Garp\Functional as f;

/**
 * Garp_Content_Cdn_Distributor
 *
 * @package Garp_Content_Cdn
 * @author David Spreekmeester <david@grrr.nl>
 * @author Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Content_Cdn_Distributor {

    /**
     * Where the baseDir for assets is located, relative to APPLICATION_PATH.
     * Without trailing slash.
     *
     * @var string
     */
    const RELATIVE_BASEDIR_AFTER_APPLICATION_PATH = '/../public';

    /**
     * System path without trailing slash.
     *
     * @var string
     */
    protected $_baseDir;

    public function __construct($path = null) {
        $this->_baseDir = realpath(
            $path ?:
            APPLICATION_PATH . self::RELATIVE_BASEDIR_AFTER_APPLICATION_PATH
        );
    }

    /**
     * @return string This instance's baseDir, without trailing slash.
     */
    public function getBaseDir() {
        return $this->_baseDir;
    }

    /**
     * Select assets to be distributed.
     *
     * @param  string $filterString
     * @param  mixed  $filterDate         Provide null for default date filter,
     *                                    false to disable filter, or a strtotime compatible
     *                                    value to set a specific date filter.
     * @return Garp_Content_Cdn_AssetList A cumulative list of relative paths to the assets.
     */
    public function select($filterString, $filterDate = null) {
        return new Garp_Content_Cdn_AssetList($this->_baseDir, $filterString, $filterDate);
    }

    /**
     * @param array                      $config    Cdn-related configuration
     * @param Garp_Content_Cdn_AssetList $assetList List of asset file paths
     * @param callable                   $successFn Function to execute after each successful
     *                                              upload). Used to report progress.
     * @param callable                   $failureFn Function to execute after each failed upload.
     * @return array Contains successfully uploaded assets.
     */
    public function distribute(
        array $config, Garp_Content_Cdn_AssetList $assetList, $successFn = null, $failureFn = null
    ) {
        $assetCount = count($assetList);

        if (f\prop('readonly', $config)) {
            throw new Garp_File_Exception(Garp_File::EXCEPTION_CDN_READONLY);
        }
        if (!$assetCount) {
            return array();
        }

        $s3 = new Garp_File_Storage_S3(
            $config,
            dirname(current($assetList)),
            true
        );

        $successFn = $successFn ?: noop();
        $failureFn = $failureFn ?: noop();

        return f\reduce(
            function ($successes, $asset) use ($s3, $successFn, $failureFn) {
                $s3->setPath(dirname($asset));
                $fileData = file_get_contents($this->_baseDir . $asset);
                $filename = basename($asset);
                if ($s3->store($filename, $fileData, true, false)) {
                    $successFn($asset);
                    return f\concat($successes, array($asset));
                }
                $failureFn($asset);
                return $successes;
            },
            array(),
            $assetList
        );
    }

}
