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
}