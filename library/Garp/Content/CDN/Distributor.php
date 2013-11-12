<?php
/**
 * Garp_Content_CDN_Distributor
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
class Garp_Content_CDN_Distributor {
	protected $_environments = array('development', 'integration', 'staging', 'production');

	/**
	 * Where the baseDir for assets is located, relative to APPLICATION_PATH. Without trailing slash.
	 */
	const RELATIVE_BASEDIR_AFTER_APPLICATION_PATH = '/../public';


	/**
	 * System path without trailing slash.
	 */
	protected $_baseDir;

	

	public function __construct() {
		$this->_baseDir = realpath(APPLICATION_PATH . self::RELATIVE_BASEDIR_AFTER_APPLICATION_PATH);
	}


	/**
	 * Returns the list of available environments.
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
	 * @param 	String 	$filterString
	 * @param 	Mixed 	$filterDate		Provide null for default date filter,
	 *									false to disable filter, or a strtotime compatible
	 *									value to set a specific date filter.
	 * @return 	Array 	$assetList 		A cumulative list of relative paths to the assets.
	 */
	public function select($filterString, $filterDate = null) {
		$assetList = new Garp_Content_CDN_AssetList($this->_baseDir, $filterString, $filterDate);
		
		return $assetList;
	}
	
	
	/**
	 * @param String $env Name of the environment, f.i. 'development' or 'production'.
	 */
	public function distribute($env, $assetList, $assetCount) {
		$this->_validateEnvironment($env);
		
		$ini = new Garp_Config_Ini(APPLICATION_PATH.'/configs/application.ini', $env);

		if (
			count($assetList) &&
			$ini->cdn->type === 's3'
		) {
			Garp_Cli::lineOut(ucfirst($env));
			$progressBar = Garp_Cli_Ui_ProgressBar::getInstance();
			$progressBar->init($assetCount);
			$firstFilename = basename($assetList[0]);
			$fileOrFiles = $this->_printFileOrFiles($assetCount);
			$progressBar->display("Processing {$firstFilename}. {$assetCount} {$fileOrFiles} left.");

			$s3 = new Garp_File_Storage_S3($ini->cdn, dirname(current($assetList)));

			foreach ($assetList as $i => $asset) {
				$s3->setPath(dirname($asset));
				$fileData = file_get_contents($this->_baseDir . $asset);
				$filename = basename($asset);
				if ($s3->store($filename, $fileData, true, false)) {
					$progressBar->advance();
					$fileOrFiles = $this->_printFileOrFiles($assetCount - $progressBar->getProgress());
					$progressBar->display("Processing {$filename}. %d {$fileOrFiles} left.");
				} else {
					$progressBar->displayError("Could not upload {$asset} to {$env}.");
				}
			}

			if ($progressBar->getProgress() === $assetCount) {
				$progressBar->display("√ Done");
			}

			echo "\n\n";
		}
	}
	
	
	protected function _validateEnvironment($env) {
		if (!in_array($env, $this->_environments)) {
			throw new Exception("'{$env}' is not a valid environment. Try: " . implode(', ', $this->_environments));
		}
	}
	
	
	protected function _printFileOrFiles($count) {
		return 'file' . ($count == 1 ? '' : 's');
	}
}
