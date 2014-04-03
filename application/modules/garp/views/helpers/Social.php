<?php
/**
 * G_View_Helper_Social
 * Helper for generating all kinds of
 * social links and assets.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class G_View_Helper_Social extends Zend_View_Helper_Abstract {
	/**
	 * Central interface for this helper.
	 * This one's always to chained to another helper method, 
	 * like so: 
	 * (in the view)
	 * $this->social()->tweetUrl(...)
	 * 
	 * @return G_View_Helper_Social $this
	 */
	public function social() {
		return $this;		
	}
	
	
	/**
	 * Generate a "Tweet this!" URL.
	 * Note that the status is automatically cut off at 140 characters.
	 * @param String $msg The tweet
	 * @param Boolean $shortenUrls Wether to shorten the URLs
	 * @return String
	 */
	public function tweetUrl($msg, $shortenUrls = true) {
		$url = 'http://twitter.com/?status=';
		if ($shortenUrls) {
			$msg = preg_replace_callback('~https?://([\w-]+\.)+[\w-]+(/[\w- ./?%&=]*)?~i', function($matches) {
				$_this = new G_View_Helper_Social();
				return $_this->tinyUrl($matches[0]);
			}, $msg);
		}
		$msg = substr($msg, 0, 140);
		$url .= urlencode($msg);
		return $url;
	}
	
	
	/**
	 * Create a Tweet button.
	 * @param Array $params
	 * @see http://twitter.com/about/resources/tweetbutton
	 * @return String
	 */
	public function tweetButton(array $params = array()) {
		$params = new Garp_Util_Configuration($params);
		$params->setDefault('url', null)
			   ->setDefault('tweet', null)
			   ->setDefault('via', null)
			   ->setDefault('related', null)
			   ->setDefault('lang', null)
			   ->setDefault('count', 'horizontal')
		;

		// set required parameters
		$attributes = array(
			'class'			=> 'twitter-share-button',
			'data-count'	=> $params['count']
		);

		// set optional attributes
		$params['url'] && $attributes['data-url'] = $params['url'];
		$params['tweet'] && $attributes['data-text'] = $params['tweet'];
		$params['via'] && $attributes['data-via'] = $params['via'];
		$params['related'] && $attributes['data-related'] = $params['related'];
		$params['lang'] && $attributes['data-lang'] = $params['lang'];
		
		$html = $this->view->htmlLink(
			'http://twitter.com/share',
			'Tweet',
			$attributes
		);

		// Add the Twitter Javascript to the stack
		// Must be rendered in the view using "$this->script()->render()"
		$this->view->script()->src('http://platform.twitter.com/widgets.js');
		return $html;
	}


	/**
	 * Generate a Hyves "Smart button" URL.
	 * @return String
	 */
	public function hyvesTipUrl($title, $body, $categoryId = 12, $rating = 5) {
		$url	= 'http://www.hyves-share.nl/button/tip/?tipcategoryid=%s&rating=%s&title=%s&body=%s';
		$title	= $title;
		$body	= $body;
		return  sprintf($url, $categoryId, $rating, $title, $body);
	}


	/**
	 * Generate a Facebook share URL
	 * @param String $url String - defaults to $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	 * @return String
	 */
	public function facebookShareUrl($url = null, $text = null) {
		$shareUrl = is_null($url) ? $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'] : $url;
		$url = 'http://facebook.com/sharer.php?u='.urlencode($shareUrl);
		if (!is_null($text)) {
			$url .= '&t='.$text;
		}
		return $url;
	}


	/**
	 * Generate a Facebook like button
	 * @param Array $params Various Facebook URL parameters
	 * @return String
	 */
	public function facebookLikeButton(array $params) {
		$params = new Garp_Util_Configuration($params);
		$params->setDefault('href', array_key_exists('url', $params) && $params['url'] ?
					$params['url'] :
					$this->view->fullUrl($this->view->url())
				)
			   ->setDefault('layout', 'button_count')
			   ->setDefault('show_faces', 'false')
			   ->setDefault('width', 450)
			   ->setDefault('height', 25)
			   ->setDefault('action', 'like')
			   ->setDefault('font', 'lucida grande')
			   ->setDefault('colorscheme', 'light')
		;
		$query = http_build_query((array)$params);
		$html  = '<iframe src="http://www.facebook.com/plugins/like.php?';
		$html .= $this->view->escape($query).'" ';
		$html .= 'style="width:'.(int)$params['width'].'px;height:';
		$html .= (int)$params['height'].'px;border:none;overflow:hidden;"';
		$html .= ' scrolling="no" frameborder="0" allowTransparency="true">';
		$html .= '</iframe>';
		return $html;
	}


	/**
	 * Generate a LinkedIn share button
	 * @param Array $params
	 * @see http://www.linkedin.com/publishers
	 * @return String
	 */
	public function linkedinShareButton(array $params = array()) {
		$html = '<script type="in/share" ';
		if (!empty($params['url'])) {
			$html .= 'data-url="'.$this->view->escape($params['url']).'" ';
		}
		if (!empty($params['counter'])) {
			$html .= 'data-counter="'.$this->view->escape($params['counter']).'" ';
		}
		$html .= '></script>';
		
		// Add the LinkedIn Javascript to the stack
		// Must be rendered in the view using "$this->script()->render()"
		$this->view->script()->src('http://platform.linkedin.com/in.js');
		return $html;
	}


	/**
	 * Shorten a URL with TinyURL
	 * @param String $url
	 * @return String
	 */
	public function tinyUrl($url) {
		$tinyurl = file_get_contents('http://tinyurl.com/api-create.php?url='.$url);
		return $tinyurl;
	}
}