<?php
/**
 * Garp_Content_Upload_FileList
 * You can use an instance of this class as a numeric array, containing an array per entry:
 * 		array(
 *			'path' => 'uploads/images/pussy.gif',
 *			'lastmodified' => '1361378985'
 *		)
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
class Garp_Content_Upload_FileList extends ArrayObject {
	const HIDDEN_FILES_PREFIX = '.';

	protected $_bannedBaseNames = array('scaled');


	/**
	 * @param $path			The relative path plus filename. F.i. '/uploads/images/pussy.gif'
	 * @param $lastmodified	Timestamp of file's last modification date.
	 */
	public function addEntry($path, $lastmodified) {
		if ($this->_isValidAssetName($path)) {
			$this[] = array(
				'path' => $path,
				'lastmodified' => $lastmodified
			);
		}
	}


	protected function _isValidAssetName($path) {
		$baseName = basename($path);
		return (
			!($baseName[0] === self::HIDDEN_FILES_PREFIX) &&
			!in_array($baseName, $this->_bannedBaseNames)
		);
	}
}