<?php
/**
 * Garp_Content_Upload_FileList
 * You can use an instance of this class as a numeric array, containing a relative path per entry:
 * 		array(
 *			'filename' => 'pussy.gif',
 *			'type' => 'document'
 *		),
 * 		array(
 *			'filename' => 'sausage.png',
 *			'type' => 'image'
 *		)
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
class Garp_Content_Upload_FileList extends ArrayObject {
	const HIDDEN_FILES_PREFIX 	= '.';
	const LABEL_FILENAME 		= 'filename';
	const LABEL_TYPE 			= 'type';

	protected $_bannedBaseNames = array('scaled');


	/**
	 * @param String $filename 	The filename, f.i. 'pussy.gif'
	 * @param String $type		The upload type, i.e. 'document' or 'image'.
	 */
	public function addEntry($filename, $type) {
		if ($this->_isValidAssetName($filename)) {
			$this[] = array(
				self::LABEL_FILENAME 	=> $filename,
				self::LABEL_TYPE 		=> $type
			);
		}
	}
	

	/**
	 * @param Array $files Numeric array of arrays, with 'type' and 'filename' elements.
	 */
	public function addEntries(array $files) {
		foreach ($files as $file) {
			$this->addEntry(
				$file[self::LABEL_FILENAME],
				$file[self::LABEL_TYPE]
			);
		}
	}


	protected function _isValidAssetName($filename) {
		return (
			!($filename[0] === self::HIDDEN_FILES_PREFIX) &&
			!in_array($filename, $this->_bannedBaseNames)
		);
	}
}