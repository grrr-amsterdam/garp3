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
		$playerurl = $video instanceof Garp_Db_Table_Row ? $video->player : $video;
		if (preg_match('~player\.vimeo\.com~', $playerurl)) {
			return $this->view->vimeo($video, $options);
		} elseif (preg_match('~youtube\.com~', $playerurl)) {
			return $this->view->youTube($video, $options);
		} else {
			throw new Exception('Unsupported media type detected: '.$playerurl);
		}
	}		
}
