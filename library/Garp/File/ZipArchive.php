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

				if (!is_link($file)) {
            		$file = realpath($file);
				}

            	if (!is_link($file) && is_dir($file)) {
                	$this->addEmptyDir(str_replace($source . '/', '', $file . '/'));
            	} else if (is_link($file)) {
					// @fixme This actually does not work. It will create a file in the zip, but it
					// won't be a link pointing somewhere.
					// I have not found a way to add a working symlink to a zip archive.
					$this->addFile($file, str_replace($source . '/', '', $file));
            	} else if (is_file($file)) {
                	$this->addFromString(str_replace($source . '/', '', $file),
 					   	file_get_contents($file));
				}
        	}
    	} else if (is_file($source) === true) {
        	$this->addFromString(basename($source), file_get_contents($source));
    	}
	}
}
