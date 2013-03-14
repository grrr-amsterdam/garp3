<?php
	
interface Garp_Content_Upload_Storage_Protocol {
	/**
	 * @return Garp_Content_Upload_FileList
	 */
	public function fetchFileList();
	
	
	/**
	 * Calculate the eTag of a file.
	 * @param 	String $filename 	Filename
	 * @param 	String $type		File type, i.e. 'document' or 'image'
	 * @return 	String 				Content hash (md5 sum of the content)
	 */
	public function fetchEtag($filename, $type);
	
	
	/**
	 * Fetches the contents of the given file.
	 * @param 	String $filename 	Filename
	 * @param 	String $type		File type, i.e. 'document' or 'image'
	 * @return 	String				Content of the file.
	 */
	public function fetchData($filename, $type);
	
	
	/**
	 * Stores given data in the file, overwriting the existing bytes if necessary.
	 * @param 	String $filename 	Filename
	 * @param 	String $type		File type, i.e. 'document' or 'image'
	 * @param String $data			File data to be stored.
	 * @return Boolean				Success of storage.
	 */
	public function store($filename, $type, &$data);
}