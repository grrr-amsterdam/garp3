<?php
/**
 * Garp_Model_Image
 * Generic image model.
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class G_Model_Image extends Model_Base_Image {
	protected $_name = 'image';
	
	public function init() {
		$this->registerObserver(new Garp_Model_Behavior_Timestampable())
		 	 ->registerObserver(new Garp_Model_Behavior_ImageScalable())
			 ->registerObserver(new Garp_Model_Validator_NotEmpty(array('filename')))
		 ;
		parent::init();
	}

	public function fetchFilenameById($id) {
		$row = $this->fetchRow($this->select()->where('id = ?', $id));
		if (!isset($row->filename)) {
			throw new Exception("Could not retrieve image record {$id}.");
		}
		return $row->filename;
	}

	public function insertFromUrl($imageUrl, $filename = null) {
		// @todo file_get_contents to optimistic?
		$bytes = file_get_contents($imageUrl);
		if (is_null($filename)) {
			$filename = $this->_createFilenameFromUrl($imageUrl);
		}
		$response = Zend_Controller_Action_HelperBroker::getStaticHelper('upload')
			->uploadRaw(Garp_File::TYPE_IMAGES, $filename, $bytes);
		return $this->insert(array(
			'filename' => $response[$filename]
		));
	}

	protected function _getImageMime($bytes) {
		$finfo = new finfo(FILEINFO_MIME);
		$mime = $finfo->buffer($bytes);
		$mime = explode(';', $mime);
		$mime = $mime[0];
		return $mime;
	}		

	protected function _createFilenameFromUrl($imageUrl) {
		$filename = basename($imageUrl);
		// Strip possible query parameters
		if (strpos($filename, '?') !== false &&
			strpos($filename, '.') !== false &&
			strpos($filename, '?') > strrpos($filename, '.')) {
			// Extract everything up until the "?"
			$filename = substr($filename, 0, strrpos($filename, '?'));
		}
		// Append extension based on mime-type
		if (strpos($filename, '.') === false) {
			$mime = $this->_getImageMime($bytes);
			if ($mime === 'application/x-gzip') {
				$bytes = gzdecode($bytes);
				$mime = $this->_getImageMime($bytes);
			}

			// Figure out mimetype, or default to jpg
			$filename .= '.' . (new Garp_File_Extension($mime) ?: 'jpg');
		}
		return $filename;
	}
}
