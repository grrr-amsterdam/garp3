<?php
/**
 * Garp_Adobe_InDesign_Storage
 * Wrapper around various InDesign related functionality.
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: david $
 * @version $Revision: 6526 $
 * @package Garp
 * @subpackage InDesign
 * @lastmodified $Date: 2012-10-04 06:20:22 +0200 (Thu, 04 Oct 2012) $
 */
class Garp_Adobe_InDesign_Storage {
	protected $_workingDir;
	
	protected $_sourcePath;
	
	protected $_targetPath;


	public function __construct($workingDir, $sourcePath, $targetPath) {
		$this->_workingDir = $workingDir;
		$this->_sourcePath = $sourcePath;
		$this->_targetPath = $targetPath;
	}


	/**
	 * Creates a temporary directory for .idml construction.
	 * @return 	String	The path to this dir, including trailing slash.
	 */
	public static function createTmpDir() {
		$tmpDir = sys_get_temp_dir();
		$tmpDir .= $tmpDir[strlen($tmpDir) - 1] !== '/' ? '/' : '';
		$tmpDir .= uniqid() . '/';
		if (mkdir($tmpDir)) {
			return $tmpDir;
		} else throw new Exception('Could not create directory ' . $tmpDir);
	}
	
	
	
	/**
	 * Extract .idml file and place it in the working directory.
	 */
	public function extract() {
		$zip = new ZipArchive();

		$res = $zip->open($this->_sourcePath);
		if ($res === true) {
			$zip->extractTo($this->_workingDir);
			$zip->close();
		} else throw new Exception('Could not open archive ' . $this->_sourcePath);
	}


	/**
	 * @param int $destination Target path of the zip, if you do not want to use $this->_targetPath
	 */
	public function zip($destination = null) {
		$source 			= $this->_workingDir;
		if (!$destination) {
			$destination 	= $this->_targetPath;
		}

		$this->_preZipChecks();

	    $zip = new ZipArchive();
	    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
			throw new Exception("Could not open a new zip archive at {$destination}.");
	    }

	    $source = str_replace('\\', '/', realpath($source));

	    if (is_dir($source) === true) {
	        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::LEAVES_ONLY);

	        foreach ($files as $file) {
	            $file = str_replace('\\', '/', $file);

	            // Ignore "." and ".." folders
	            if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) )
	                continue;

	            $file = realpath($file);

	            if (is_dir($file) === true) {
	                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
	            } elseif (is_file($file) === true) {
	                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
	            }
	        }
	    } elseif (is_file($source) === true) {
	        $zip->addFromString(basename($source), file_get_contents($source));
	    }

	    $zip->close();
	}
	
	
	protected function _preZipChecks() {
	    if (!extension_loaded('zip')) {
			throw new Exception('Zip PHP extension is not installed :(');
		}
		
		if (!file_exists($this->_workingDir)) {
			throw new Exception("Specified source file {$this->_workingDir} does not exist.");
	    }
	}
	
	
	public function removeWorkingDir() {
		$this->_recursiveRmDir($this->_workingDir);
	}
	
	
	/**
	 * @param	String	$dir	Trailing slash is mandatory.
	 */
	protected function _recursiveRmDir($dir) {
	    foreach(glob($dir . '*') as $file) {
	        if(is_dir($file))
	            $this->_recursiveRmDir($file . '/');
	        else
	            unlink($file);
	    }
	    rmdir($dir);
	}

}