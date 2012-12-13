<?php
/**
 * Garp_Content_Distributor
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
class Garp_Content_Distributor {
	protected $_environments = array('development', 'integration', 'staging', 'production');

	protected $_bannedNodeSubstrings = array('.php', '.psd', 'uploads', 'cached', 'sass');

	/**
	 * System path without trailing slash.
	 */
	protected $_baseDir;

	

	public function __construct() {
		$this->_baseDir = realpath(APPLICATION_PATH . '/../public');
	}


	/**
	 * Returns the list of available environments.
	 */
	public function getEnvironments() {
		return $this->_environments;
	}


	
	/**
	 * Select assets to be distributed.
	 * @param 	String 	$filterString
	 * @return 	Array 	$assetList A cumulative list of relative paths to the assets.
	 */
	public function select($filterString) {
		$assetList = $this->_getAssetPaths($filterString);
		return $assetList;
	}
	
	
	
	/**
	 * @param String $env Name of the environment, f.i. 'development' or 'production'.
	 */
	public function distribute($env, $assetList, $assetCount) {
		$this->_validateEnvironment($env);
		
		$ini = new Zend_Config_Ini(APPLICATION_PATH.'/configs/application.ini', $env);

		if ($ini->cdn->type === 's3') {
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
	

	/**
	 * Retrieves all the relative paths to the asset files in the provided folder, and the folders below that.
	 * @param 	String	$filterString 		The string to filter the paths through.
	 * @param 	String	$subDir 			Optional subfolder provided when crawling the tree, excluding preceding and trailing slash.
	 * @return 	Array 	$assetList 			A cumulative list of relative paths to the assets.
	 */
	protected function _getAssetPaths($filterString, $subDir = null) {
		$assetList 		= array();
		$subDirPostfix	= $subDir ? (DIRECTORY_SEPARATOR . $subDir) : '';
		$absDir 		= $this->_baseDir . $subDirPostfix;

		if ($handle = opendir($absDir)) {
			while (false !== ($nodeName = readdir($handle))) {
				if ($this->_isValidAssetName($nodeName)) {
					$relNodePath = $subDirPostfix . DIRECTORY_SEPARATOR . $nodeName;
					$absNodePath = $this->_baseDir . DIRECTORY_SEPARATOR . $nodeName;

					if (is_dir($absNodePath)) {
						$assetList += $this->_getAssetPaths($filterString, $relNodePath);
					} else {
						if (
							!$filterString ||
							stripos($relNodePath, $filterString) !== false
						) {
							$assetList[] = DIRECTORY_SEPARATOR . $relNodePath;
						}
					}
				}
			}
		} else throw new Exception('Unable to open the configuration directory at ' . $absDir);
		
		return $assetList;
	}



	protected function _isValidAssetName($filename) {
		if ($filename[0] === '.') {
			return false;
		} else {
			foreach ($this->_bannedNodeSubstrings as $bannedSubstring) {
				if (strpos($filename, $bannedSubstring) !== false) {
					return false;
				}
			}
		}

		return true;
	}
}