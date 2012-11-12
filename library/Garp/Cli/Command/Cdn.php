<?php
/**
 * Garp_Cli_Command_Cdn
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cli
 * @lastmodified $Date: $
 */
class Garp_Cli_Command_Cdn extends Garp_Cli_Command {
	protected $_environments = array('development', 'staging', 'production');

	protected $_bannedNodeSubstrings = array('.php', '.psd', 'uploads', 'cached', 'sass');

	protected $_baseDir;



	/**
	 * Central start method
	 * @param Array $args Various options. Must contain;
	 * ['t']	String	Table name.
	 * @return Void
	 */
	public function main(array $args = array()) {
		$this->_baseDir = realpath(APPLICATION_PATH . '/../public');

		if (
			empty($args) ||
			!array_key_exists(1, $args)
		) {
			Garp_Cli::errorOut("Awaiting your orders.\n");
			$this->_displayHelpText();
		} else {
			$command = $args[1];

			if (method_exists($this, '_'.$command)) {
				$this->{'_'.$command}($args);
			} else {
				Garp_Cli::errorOut("Sorry, I don't know the command '{$command}'.\n");
				$this->_displayHelpText();
			}
		}
	}


	/**
	 * Distributes the public assets on the local server to the configured CDN servers.
	 */
	protected function _distribute($args) {
		$filterString = array_key_exists(2, $args) ? $args[2] : null;

		$assetList = array();
		$this->_getAssetPaths($assetList, $filterString);

		if ($assetList) {
			$assetCount = count($assetList);
			$summary = $assetCount === 1 ? $assetList[0] : $assetCount . ' assets.';
			Garp_Cli::lineOut("Distributing {$summary}\n");

			if (array_key_exists('to', $args)) {
				$this->_distributePerEnvironment($args['to'], $assetList, $assetCount);
			} else {
				foreach ($this->_environments as $env) {
					$this->_distributePerEnvironment($env, $assetList, $assetCount);
				}
			}
		} else Garp_Cli::errorOut("No files to distribute.");
	}
	
	
	/**
	 * @param String $env Name of the environment, f.i. 'development' or 'production'.
	 */
	protected function _distributePerEnvironment($env, &$assetList, $assetCount) {
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
	
	
	protected function _printFileOrFiles($count) {
		return 'file' . ($count == 1 ? '' : 's');
	}
	

	/**
	 * Retrieves all the relative paths to the asset files in the provided folder, and the folders below that.
	 * @param Array &$assetList Reference to a cumulative list of relative paths to the assets.
	 * @param String $subDir Optional subfolder provided when crawling the tree, excluding preceding and trailing slash.
	 */
	protected function _getAssetPaths(Array &$assetList, $filterString, $subDir = null) {
		$absDir = $this->_baseDir . ($subDir ? ('/' . $subDir) : '');
		
		if ($handle = opendir($absDir)) {
			while (false !== ($nodeName = readdir($handle))) {
				if ($this->_isValidAssetName($nodeName)) {
					$relNodePath = ($subDir ? ($subDir . '/') : '') . $nodeName;

					if (is_dir($this->_baseDir . '/' . $relNodePath)) {
						$this->_getAssetPaths($assetList, $filterString, $relNodePath);
					} else {
						if (
							!$filterString ||
							stripos($relNodePath, $filterString) !== false
						) {
							$assetList[] = '/' . $relNodePath;
						}
					}
				}
			}
		} else throw new Exception('Unable to open the configuration directory at ' . $absDir);
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


	protected function _displayHelpText() {
		Garp_Cli::lineOut("☞  U s a g e :\n");
		Garp_Cli::lineOut("Distributing all assets to the CDN servers:");
		Garp_Cli::lineOut("\tgarp.php Cdn distribute");
		Garp_Cli::lineOut("");

		Garp_Cli::lineOut("Examples of distributing a specific set of assets to the CDN servers:");
		Garp_Cli::lineOut("\tgarp.php Cdn distribute main.js");
		Garp_Cli::lineOut("\tgarp.php Cdn distribute css");
		Garp_Cli::lineOut("\tgarp.php Cdn distribute css/icons");
		Garp_Cli::lineOut("\tgarp.php Cdn distribute logos");
		Garp_Cli::lineOut("");
		
		Garp_Cli::lineOut("Distributing to a specific environment:");
		Garp_Cli::lineOut("\tgarp.php Cdn distribute --to=development");
		Garp_Cli::lineOut("\tgarp.php Cdn distribute main.js --to=staging");
		Garp_Cli::lineOut("");
	}
}