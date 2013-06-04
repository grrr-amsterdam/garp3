<?php
/**
 * G_View_Helper_Social
 * Helper for generating all kinds of
 * social links and assets.
 * @author Harmen Janssen, David Spreekmeester | grrr.nl
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
			   ->setDefault('text', null)
			   ->setDefault('via', null)
			   ->setDefault('related', null)
			   ->setDefault('lang', null)
			   ->setDefault('count', 'horizontal')
			   ->setDefault('loadScript', true)
		;

		// set required parameters
		$attributes = array(
			'class'			=> 'twitter-share-button',
			'data-count'	=> $params['count']
		);

		// set optional attributes
		$params['url'] && $attributes['data-url'] = $params['url'];
		$params['text'] && $attributes['data-text'] = $params['text'];
		$params['via'] && $attributes['data-via'] = $params['via'];
		$params['related'] && $attributes['data-related'] = $params['related'];
		$params['lang'] && $attributes['data-lang'] = $params['lang'];
		
		$html = $this->view->htmlLink(
			'http://twitter.com/share',
			'Tweet',
			$attributes
		);

		if ($params['loadScript']) {
			// Add the Twitter Javascript to the stack
			// Must be rendered in the view using "$this->script()->render()"
			$this->view->script()->src('http://platform.twitter.com/widgets.js');
		}
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
	 * This method needs to be run at about the end of the HTML BODY tag.
	 * It fires up all necessaries for Facebook's SDK, which is required for XFBML tags.
	 * Also, the HTML tag itself should have the xmlns:fb="http://www.facebook.com/2008/fbml" attribute.
	 */
	public function facebookInit() {
		if ($appId = $this->facebookAppId()) {
			$channelUrl = $this->facebookChannelUrl();

			return $this->view->partial('partials/social/facebook/init.phtml', 'g', array(
				'appId' => $appId,
				'channelUrl' => $channelUrl
			));
		} else throw new Exception(
			'Please fill out auth.adapters.facebook.appId with the id you'
			.' retrieve from your friendly system administrator.'
		);
	}


	/**
 	 * Get Facebook App Id
 	 * @return String
 	 */
	public function facebookAppId() {
		return $this->view->config()->auth->adapters->facebook->appId;
	}

	/**
 	 * Get Facebook channel URL
 	 * @return String
 	 */
	public function facebookChannelUrl() {
		return $this->view->fullUrl("/js/garp/social/facebook/channel.php");
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
	public function facebookLikeButton(array $params = array(), $useFacebookPageAsUrl = false) {
		$params = new Garp_Util_Configuration($params);
		$params->setDefault('href', array_key_exists('href', $params) && $params['href'] ?
					$params['href'] :
					$this->_getCurrentUrl()
				)
				->setDefault('layout', 'button_count')
			   ->setDefault('show_faces', 'false')
			   ->setDefault('width', 450)
			   ->setDefault('action', 'like')
			   ->setDefault('font', 'lucida grande')
			   ->setDefault('colorscheme', 'light')
		;
		
		if ($useFacebookPageAsUrl) {
			$this->_setFacebookPageUrlAsHref($params);
		}

		$html = '<fb:like '. $this->_renderHtmlAttribs($params) .'></fb:like>';
		return $html;
	}
	
	
	/**
	 * Generate a Facebook recommend button (which is a like button, but with a different action / label)
	 * @param Array $params Various Facebook URL parameters
	 * @return String
	 */
	public function facebookRecommendButton(array $params = array(), $useFacebookPageAsUrl = false) {
		$params['action'] = 'recommend';
		return $this->facebookLikeButton($params, $useFacebookPageAsUrl);
	}
	
	
	/**
	 * Display Facebook comments widget
	 * @param Array $params Various Facebook URL parameters
	 * @return String
	 */
	public function facebookComments(array $params = array()) {
		$params = new Garp_Util_Configuration($params);
		$params->setDefault('href', array_key_exists('href', $params) && $params['href'] ?
					$params['href'] :
					$this->view->fullUrl($this->view->url())
				)
				->setDefault('width', 400) /* Minimum recommended width: 400 */
			   ->setDefault('num_posts', 10)
			   ->setDefault('colorscheme', 'light')
		;

		$html = '<fb:comments '. $this->_renderHtmlAttribs($params) .'></fb:comments>';
		return $html;
	}


	/**
	 * Generate a Facebook like button
	 * @param Array $params Various Facebook URL parameters
	 * @return String
	 */
	public function facebookFacepile(array $params = array(), $useFacebookPageAsUrl = false) {
		$params = new Garp_Util_Configuration($params);
		$params->setDefault('href', array_key_exists('href', $params) && $params['href'] ?
					$params['href'] :
					$this->_getCurrentUrl()
				)
			   ->setDefault('max_rows', 1)
			   ->setDefault('width', 450)
			   ->setDefault('colorscheme', 'light')
		;
		
		if ($useFacebookPageAsUrl) {
			$this->_setFacebookPageUrlAsHref($params);
		}

		$html = '<fb:facepile '. $this->_renderHtmlAttribs($params) .'></fb:facepile>';
		return $html;
	}


	/**
 	 * Print Facebook Open Graph tags.
 	 * @param Array $ogData The Open Graph information
 	 * @return String The HTML
 	 */
	public function facebookOgData(array $ogData = array()) {
		$html = '';
		$metaTemplate = '<meta property="%s" content="%s">';
		$ini = Zend_Registry::get('config');
		
		if (!array_key_exists('admins', $ogData)) {
			if ($ini->auth->adapters->facebook->admins) {
				$ogData['admins'] = $ini->auth->adapters->facebook->admins;
			} else throw new Exception("The auth.adapters.facebook.admins configuration parameter is missing. Please use a numeric id, not a username.");
		}

		if (!array_key_exists('app_id', $ogData)) {
			if ($ini->auth->adapters->facebook->appId) {
				$ogData['app_id'] = $ini->auth->adapters->facebook->appId;
			} else throw new Exception("The auth.adapters.facebook.appId configuration parameter is missing.");
		}

		// Set some defaults
		if (empty($ogData['url']) && isset($_SERVER['REQUEST_URI'])) {
			$ogData['url'] = $this->view->fullUrl($_SERVER['REQUEST_URI']);
		}
		if (empty($ogData['type'])) {
			if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] == '/') {
				$ogData['type'] = 'website';
			} else {
				$ogData['type'] = 'article';
			}
		}
		if (empty($ogData['title']) && $this->view->title) {
			$ogData['title'] = $this->view->title;
			if ($appName = $this->view->config()->app->name) {
				$ogData['title'] .= ' | '.$appName;
			}
		}
		if (empty($ogData['image']) && !empty($this->view->config()->app->image)) {
			$ogData['image'] = $this->view->assetUrl($this->view->config()->app->image);
		}
		if (empty($ogData['description'])) {
			if ($this->view->description) {
			} elseif (!empty($this->view->config()->app->description)) {
				$ogData['description'] = $this->view->config()->app->description;
			}
		}
		if (empty($ogData['locale']) && !empty($this->view->config()->app->locale)) {
			$ogData['locale'] = $this->view->config()->app->locale;
		}

		foreach ($ogData as $ogKey => $ogValue) {
			$prefix   = in_array($ogKey, array('admins', 'app_id')) ? 'fb' : 'og';
			$metaHtml = sprintf($metaTemplate, $this->view->escape($prefix.':'.$ogKey), $this->view->escape($ogValue));
			$html    .= "$metaHtml\n";
		}
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
	
	
	/**
	 * Returns current url, stripped of any possible url queries.
	 */
	protected function _getCurrentUrl() {
		$url = $this->view->fullUrl($this->view->url());
		$quesPos = strpos($url, '?');
		if ($quesPos !== false) {
			$url = substr($url, 0, $quesPos);
		}
		
		return $url;
	}
	
	
	/**
	 * Renders key / value pairs to an HTML attributes string.
	 * @param Garp_Util_Configuration $attribs The configuration object
	 */
	protected function _renderHtmlAttribs(Garp_Util_Configuration $attribs) {
		$attributePairs = array();
		foreach ($attribs as $attribName => $attribValue) {
			$attributesPairs[] = $attribName . '="'. $attribValue .'"';
		}
		return implode(' ', $attributesPairs);
	}
	
	
	protected function _setFacebookPageUrlAsHref(Garp_Util_Configuration $params) {
		$ini = Zend_Registry::get('config');
		if ($ini->organization->facebook) {
			$params['href'] = $ini->organization->facebook;
		} else throw new Exception("Missing url: organization.facebook in application.ini");
	}
}
