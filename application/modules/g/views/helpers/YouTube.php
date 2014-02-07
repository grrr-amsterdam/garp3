<?php
/**
 * G_View_Helper_YouTube
 * Generate HTML Object tags to a YouTube video
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Helper
 * @lastmodified $Date: $
 */
class G_View_Helper_YouTube extends Zend_View_Helper_HtmlElement {
	/**
	 * Render a youtube object tag.
	 * If no arguments are given, $this is returned 
	 * so there can be some chaining.
	 * @param Garp_Db_Table_Row $youtube A record from the `youtube_videos` table (or `media` view)
	 * @param Array $options Various rendering options
	 * @return String
	 */
	public function youTube($youtube = null, array $options = array()) {
		if (!func_num_args()) {
			return $this;
		}
		return $this->render($youtube, $options);
	} 
	
	/**
	 * Render a youtube object tag.
	 * @param Garp_Db_Table_Row|String $youtube A record from the `youtube_videos` table (or `media` view), or a url to a YouTube video.
	 * @param Array $options Various rendering options
	 * @return String
	 */
	public function render($youtube, array $options = array()) {
		$this->_setDefaultAttribs($options);

		$_attribs = $options['attribs'];
		$_attribs['width'] = $options['width'];
		$_attribs['height'] = $options['height'];
		$_attribs['frameborder'] = '0';
		$_attribs['allowfullscreen'] = 'allowfullscreen';
		
		// unset the parameters that are not part of the query string
		unset($options['width']);
		unset($options['height']);
		unset($options['attribs']);

		// create the YouTube URL
		$youtubeUrl = $this->getPlayerUrl($youtube);
		$_attribs['src'] = $youtubeUrl;

		$html = '<iframe'.$this->_htmlAttribs($_attribs).'></iframe>'; 
		return $html;
	}
	
	public function getPlayerUrl($youtube, $options = array()) {
		$this->_setDefaultQueryParams($options);
		$youtubeUrl  = $youtube instanceof Garp_Db_Table_Row ? $youtube->player : $youtube;
		if (strpos($youtubeUrl, '?') === false) {
			$youtubeUrl .= '?';
		} else {
			$youtubeUrl .= '&';
		}
		$youtubeUrl .= http_build_query($options);
		return $youtubeUrl;
	}		
	
	/**
	 * Normalize some configuration values.
	 * @param Array $options
	 */
	protected function _setDefaultAttribs(&$options) {
		$options = $options instanceof Garp_Util_Configuration ? $options : new Garp_Util_Configuration($options);
		$options
			->setDefault('height', isset($options['width']) ? 30 + round($options['width']/1.78) : 300)
			->setDefault('width', 480)
			->setDefault('attribs', array())
			->setDefault('wmode', 'opaque')
		;
	}

	/**
	 * Normalize some query parameters
	 * @see https://developers.google.com/youtube/player_parameters
	 * @param Array $options
	 */
	protected function _setDefaultQueryParams(&$options) {
		$options = $options instanceof Garp_Util_Configuration ? $options : new Garp_Util_Configuration($options);
		$options
			->setDefault('rel', '0')
			->setDefault('showinfo', '0')
			->setDefault('fs', '1')
			->setDefault('modestbranding', '1')
			->setDefault('theme', 'light')
		;
	}
}
