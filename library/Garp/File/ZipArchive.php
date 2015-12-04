<?php
/**
 * Garp_File_ZipArchive
 * Extends ZipArchive to add a much needed addDirectory() method.
 * Based on function Zip from
 * @see http://stackoverflow.com/questions/1334613/how-to-recursively-zip-a-directory-in-php
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      Garp_File
 */
class Garp_File_ZipArchive extends ZipArchive {
	/**
 	 * Add the complete contents of a directory, recursively.
 	 */
	public function addDirectory($source) {
    	$source = str_replace('\\', '/', realpath($source));

    	if (is_dir($source) === true) {
        	$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

        	foreach ($files as $file) {
            	$file = str_replace('\\', '/', $file);

            	// Ignore "." and ".." folders
            	if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) )
                	continue;

            	$file = realpath($file);

            	if (is_dir($file) === true) {
                	$this->addEmptyDir(str_replace($source . '/', '', $file . '/'));
            	} else if (is_file($file) === true) {
                	$this->addFromString(str_replace($source . '/', '', $file),
 					   	file_get_contents($file));
            	}
        	}
    	} else if (is_file($source) === true) {
        	$this->addFromString(basename($source), file_get_contents($source));
    	}
	}
}
