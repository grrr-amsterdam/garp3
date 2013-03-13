<?php
/**
 * Garp_Content_Upload_FileNode
 * Representation of an uploaded file, containing filename and upload type (i.e. document / image).
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
class Garp_Content_Upload_FileNode {
	const HIDDEN_FILES_PREFIX 	= '.';

	/**
	 * @var String $_filename
	 */
	protected $_filename;

	/**
	 * @var String $_type
	 */
	protected $_type;

	protected $_bannedBaseNames = array('scaled');


	public function __construct($filename, $type) {
		$this->setFilename($filename);
		$this->setType($type);
		$this->_validate();
	}

	
	/**
	 * @return String
	 */
	public function getFilename() {
		return $this->_filename;
	}
	
	/**
	 * @param String $filename
	 */
	public function setFilename($filename) {
		$this->_filename = $filename;
	}
	
	/**
	 * @return String
	 */
	public function getType() {
		return $this->_type;
	}
	
	/**
	 * @param String $type
	 */
	public function setType($type) {
		$this->_type = $type;
	}
	
	protected function _hasValidName() {
		$filename = $this->getFilename();

		return (
			!($filename[0] === self::HIDDEN_FILES_PREFIX) &&
			!in_array($filename, $this->_bannedBaseNames)
		);
	}
	
	protected function _validate() {
		$filename = $this->getFilename();

		if (!$filename) {
			throw new Exception("The filename is required.");
		}
		
		if (!$this->_hasValidName()) {
			throw new Exception("The filename is invalid ('{$filename}').");
		}
		
		if (!$this->getType()) {
			throw new Exception("The upload type ('type') property is required.");
		}
	}
}

