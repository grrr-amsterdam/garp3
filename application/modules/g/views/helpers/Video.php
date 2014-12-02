<?php
/**
 * G_View_Helper_Video
 * Generic video helper. Can render YouTube and Vimeo.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Helper
 * @lastmodified $Date: $
 */
class G_View_Helper_Video extends Zend_View_Helper_Abstract {
	/**
	 * Render a video player.
	 * @param Garp_Db_Table_Row $video A record from a video table
	 * @param Array $options Various rendering options
	 * @return Mixed
	 */
	public function video($video = null, array $options = array()) {
		if (!func_num_args()) {
			// provide fluent interface
			return $this;
		}
		if (!$video) {
			throw new InvalidArgumentException(__METHOD__.' expects parameter 1 to be Garp_Db_Table_Row');
		}
		return $this->render($video, $options);
	}

	/**
 	 * Render a video player.
	 * @param Garp_Db_Table_Row $video A record from a video table
	 * @param Array $options Various rendering options
	 * @return Mixed
 	 */
	public function render($video, $options = array()) {
		$helper = $this->_getSpecializedHelper($video);
		return $helper->render($video, $options);
	}

	/**
 	 * Get only a player URL. Some sensible default parameters will be applied.
	 * @param Garp_Db_Table_Row $video A record from a video table
	 * @param Array $options Various rendering options
 	 */
	public function getPlayerUrl($video, $options = array()) {
		$helper = $this->_getSpecializedHelper($video);
		return $helper->getPlayerUrl($video, $options);
	}

	/**
 	 * Check if video is Vimeo
 	 */
	public function isVimeo($video) {
		$playerurl = $video instanceof Garp_Db_Table_Row ? $video->player : $video;
		return preg_match('~player\.vimeo\.com~', $playerurl);
	}

	/**
 	 * Check if video is Youtube
 	 */
	public function isYoutube($video) {
		$playerurl = $video instanceof Garp_Db_Table_Row ? $video->player : $video;
		return preg_match('~youtube\.com~', $playerurl);
	}		

	/**
 	 * Return either the Vimeo or YouTube helper
 	 * @return Zend_View_Helper_Abstract
 	 */
	protected function _getSpecializedHelper($video) {
		if ($this->isVimeo($video)) {
			return $this->view->vimeo();
		} elseif ($this->isYoutube($video)) {
			return $this->view->youTube();
		}
		throw new Exception('Unsupported media type detected: '.$playerurl);
	}
	
}
