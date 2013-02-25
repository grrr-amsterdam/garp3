<?php
	
interface Garp_Content_Upload_Storage_Protocol {
	/**
	 * @return Garp_Content_Upload_FileList
	 */
	public function fetchFileList();
}