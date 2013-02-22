<?php
	
interface Garp_Content_Upload_Storage_Behavior_Listable {
	/**
	 * @return Garp_Content_Upload_FileList
	 */
	public function fetchFileList();
}