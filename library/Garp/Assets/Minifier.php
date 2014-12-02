<?php
/**
 * Garp_Assets_Minifier
 * Minifies assets.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Garp_Assets
 */
class Garp_Assets_Minifier {
	/**
 	 * Asset root
 	 * @var String
 	 */
	protected $_assetRoot;


	/**
 	 * Class constructor
 	 * @param String $assetRoot Path to the root of all assets
 	 * @return Void
 	 */
	public function __construct($assetRoot) {
		$this->setAssetRoot($assetRoot);
	}

	/**
 	 * Set asset root
 	 * @param String $root
 	 * @return Void
 	 */
	public function setAssetRoot($root) {
		$this->_assetRoot = realpath(rtrim($root, DIRECTORY_SEPARATOR));
	}


	/**
 	 * Get asset root
	 * @return String
	 */
	public function getAssetRoot() {
		return $this->_assetRoot;
	}


	/**
 	 * Minify and concat a bunch of Javascript files into one 
 	 * tiny little file.
 	 * @param Array|String $sourceFileList The original files
 	 * @param String $targetFile The target filename
 	 * @return Boolean
 	 * @todo Add option that suppresses output
 	 */
	public function minifyJs($sourceFileList, $targetFile) {
		$sourceFileList = (array)$sourceFileList;
		$sourceFileList = array_map(array($this, '_createFullPath'), $sourceFileList);
		$sourceFileList = implode(' ', $sourceFileList);
		$targetFile = $this->_createFullPath($targetFile);

		$uglifyJsCommand = 'uglifyjs '.$sourceFileList.' -o '.$targetFile.' -c -m';
		`$uglifyJsCommand`;
	}


	/**
 	 * Create full path to asset
 	 * @param String $path Relative path
 	 * @return String
 	 */
	protected function _createFullPath($path) {
		$path = $this->_assetRoot.DIRECTORY_SEPARATOR.$path;
		return $path;
	}
}
