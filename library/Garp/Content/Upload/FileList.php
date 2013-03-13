<?php
/**
 * Garp_Content_Upload_FileList
 * You can use an instance of this class as a numeric array, containing Garp_Content_Upload_FileNode elements.
 *
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
class Garp_Content_Upload_FileList extends ArrayObject {
	/**
	 * @param String $filename 	The filename, f.i. 'pussy.gif'
	 * @param String $type		The upload type, i.e. 'document' or 'image'.
	 */
	public function addEntry(Garp_Content_Upload_FileNode $file) {
		if ($file->isValid()) {
			$this[] = $file;
		}
	}
	
	/**
	 * @param Garp_Content_Upload_FileList $files List of Garp_Content_Upload_FileNode elements.
	 */
	public function addEntries(Garp_Content_Upload_FileList $files) {
		foreach ($files as $file) {
			$this->addEntry($file);
		}
	}
	
	/**
	 * @return Garp_Content_Upload_FileList Intersecting files, matching filenames and types
	 */
	public function findIntersecting(Garp_Content_Upload_FileList $thoseFiles) {
		$intersecting = new Garp_Content_Upload_FileList();

		foreach ($this as $thisFile) {
			foreach ($thoseFiles as $thatFile) {
				if ($thisFile == $thatFile) {
					$intersecting->addEntry($thisFile);
				}
			}
		}
		
		return $intersecting;
	}

	/**
	 * @param	Garp_Content_Upload_FileList $thoseFiles	The file list to check for corresponding files
	 * @return 	Garp_Content_Upload_FileList 				Files that are unique and do not exist in $thoseFiles
	 */
	public function findUnique(Garp_Content_Upload_FileList $thoseFiles) {
		$unique = new Garp_Content_Upload_FileList();

		foreach ($this as $thisFile) {
			if (!$thoseFiles->nodeExists($thisFile)) {
				$unique->addEntry($thisFile);
			}
		}

		return $unique;
	}
	
	/**
	 * @return 	Bool	Whether the file node exists in this list
	 */
	public function nodeExists(Garp_Content_Upload_FileNode $thatFile) {
		foreach ($this as $thisFile) {
			if ($thisFile == $thatFile) {
				return true;
			}
		}
		
		return false;
	}
}