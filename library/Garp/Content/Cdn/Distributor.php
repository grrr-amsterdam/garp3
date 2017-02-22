<?php
/**
 * Garp_Content_Cdn_Distributor
 *
 * @package Garp
 * @subpackage Content
 * @author David Spreekmeester <david@grrr.nl>
 * @version $Revision: $
 * @modifiedby $LastChangedBy: $
 * @lastmodified $Date: $
 */
class Garp_Content_Cdn_Distributor {
    protected $_environments = array('development', 'integration', 'staging', 'production');

    /**
     * Where the baseDir for assets is located, relative to APPLICATION_PATH.
     * Without trailing slash.
     */
    const RELATIVE_BASEDIR_AFTER_APPLICATION_PATH = '/../public';

    /**
     * System path without trailing slash.
     */
    protected $_baseDir;

    public function __construct($path = null) {
        $this->_baseDir = realpath(
            $path ?:
            APPLICATION_PATH . self::RELATIVE_BASEDIR_AFTER_APPLICATION_PATH
        );
    }

    /**
     * Returns the list of available environments.
     * 
     * @return Array The list of environments.
     */
    public function getEnvironments() {
        return $this->_environments;
    }

    /**
     * @return String This instance's baseDir, without trailing slash.
     */
    public function getBaseDir() {
        return $this->_baseDir;
    }

    /**
     * Select assets to be distributed.
     *
     * @param   String  $filterString
     * @param   Mixed   $filterDate     Provide null for default date filter,
     *                                  false to disable filter, or a strtotime compatible
     *                                  value to set a specific date filter.
     * @return  Array   $assetList      A cumulative list of relative paths to the assets.
     */
    public function select($filterString, $filterDate = null) {
        return new Garp_Content_Cdn_AssetList($this->_baseDir, $filterString, $filterDate);
    }

    /**
     * @param String    $env        Name of the environment, f.i. 'development' or 'production'.
     * @param Array     $assetList  List of asset file paths
     * @param Int       $assetCount Number of assets
     * @return Void
     */
    public function distribute($env, $assetList, $assetCount) {
        $this->_validateEnvironment($env);

        $ini = new Garp_Config_Ini(APPLICATION_PATH . '/configs/application.ini', $env);
        if ($ini->cdn->readonly) {
            throw new Garp_File_Exception(Garp_File::EXCEPTION_CDN_READONLY);
        }

        if (!$assetCount || $ini->cdn->type !== 's3') {
            return;
        }

        Garp_Cli::lineOut(ucfirst($env));
        $s3 = new Garp_File_Storage_S3($ini->cdn, dirname(current($assetList)), true);

        foreach ($assetList as $i => $asset) {
            $s3->setPath(dirname($asset));
            $fileData = file_get_contents($this->_baseDir . $asset);
            $filename = basename($asset);
            if ($s3->store($filename, $fileData, true, false)) {
                echo '.';
            } else { 
                Garp_Cli::errorOut("\nCould not upload {$asset} to {$env}.");
            }
        }

        Garp_Cli::lineOut("\n√ Done");

        echo "\n\n";
    }

    protected function _validateEnvironment($env) {
        if (!in_array($env, $this->_environments)) {
            throw new Exception(
                "'{$env}' is not a valid environment. Try: "
                . implode(', ', $this->_environments)
            );
        }
    }

    protected function _printFileOrFiles($count) {
        return 'file' . ($count == 1 ? '' : 's');
    }
}
