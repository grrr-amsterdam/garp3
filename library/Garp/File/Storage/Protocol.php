<?php
/**
 * Storage and retrieval of user uploads.
 * @author David Spreekmeester | Grrr.nl
 * @package Garp
 */
interface Garp_File_Storage_Protocol {
	public function __construct(Zend_Config $config, $path);

	public function exists($filename);

	/** Fetches the url to the file, suitable for public access on the web. */
	public function getUrl($filename);

	/** Fetches the file data and returns resource. */
	public function fetch($filename);

	/** Lists all valid files in the upload directory. */
	public function getList();

	/** Returns mime type of given file. */
	public function getMime($filename);

	/** Returns file size in bytes */
	public function getSize($filename);

	/** Returns last modified time of file, as a Unix timestamp. */
	public function getTimestamp($filename);

	/** @return String Destination filename. */
	public function store($filename, $data, $overwrite, $formatFilename);


	public function remove($filename);
}