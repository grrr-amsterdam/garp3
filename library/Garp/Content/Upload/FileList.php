<?php
/**
 * Garp_Content_Upload_FileList
 * You can use an instance of this class as a numeric array, containing a relative path per entry:
 * 		array(
 *			'/uploads/images/pussy.gif',
 *			'/uploads/images/kitten.jpg'
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
	 * @param String $path The relative path plus filename. F.i. '/uploads/images/pussy.gif'
	 */
	public function addEntry($path) {
		if ($this->_isValidAssetName($path)) {
			$this[] = $path;
		}
	}
	

	/**
	 * @param Array $paths Numeric array of relative paths plus filename. F.i. array('/uploads/images/pussy.gif')
	 */
	public function addEntries(array $paths) {
		foreach ($paths as $path) {
			$this->addEntry($path);
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