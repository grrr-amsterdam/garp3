<?php
	
interface Garp_Content_Upload_Storage_Protocol {
	/**
	 * @return Garp_Content_Upload_FileList
	 */
	public function fetchFileList();
	
	
	/**
	 * Calculate the eTag of a file.
	 * @param String $path 	Relative path to the file, starting with a slash.
	 * @return String 		Content hash (md5 sum of the content)
	 */
	public function fetchEtag($path);
	
	
	/**
	 * Fetches the contents of the given file.
	 * @param String $path 	Relative path to the file, starting with a slash.
	 * @return String		Content of the file.
	 */
	public function fetchData($path);
	
	
	/**
	 * Stores given data in the file, overwriting the existing bytes if necessary.
	 * @param String $path 	Relative path to the file, starting with a slash.
	 * @param String $data	File data to be stored.
	 * @return Boolean		Success of storage.
	 */
	public function store($path, $data);
}