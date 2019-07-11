<?php
use function \Garp\__;

/**
 * G_View_Helper_Social
 * Helper for generating all kinds of
 * social links and assets.
 *
 * @package G_View_Helper
 * @author  Harmen Janssen <harmen@grrr.nl>
 * @author  David Spreekmeester <david@grrr.nl>
 */
class G_View_Helper_Social extends Zend_View_Helper_Abstract {
    /**
     * Wether the current instance uses a Facebook plugin that requires Facebook init.
     *
     * @var bool
     */
    protected $_needsFacebookInit = false;

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
     *
     * @param string $msg The tweet
     * @param bool $shortenUrls Wether to shorten the URLs
     * @return string
     */
    public function tweetUrl($msg, $shortenUrls = false) {
        $url = 'http://twitter.com/?status=';
        if ($shortenUrls) {
            $msg = preg_replace_callback(
                '~https?://([\w-]+\.)+[\w-]+(/[\w- ./?%&=]*)?~i',
                function ($matches) {
                    $_this = new G_View_Helper_Social();
                    return $_this->tinyUrl($matches[0]);
                },
                $msg
            );
        }
        $url .= urlencode($msg);
        return $url;
    }

    /**
     * Generate a Whatsapp Share URL.
     *
     * @param string $msg The tweet
     * @param bool $shortenUrls Wether to shorten the URLs
     * @return string
     */
    public function whatsappUrl($msg, $shortenUrls = false) {
        $url = 'whatsapp://send?text=';
        if ($shortenUrls) {
            $msg = preg_replace_callback(
                '~https?://([\w-]+\.)+[\w-]+(/[\w- ./?%&=]*)?~i',
                function ($matches) {
                    $_this = new G_View_Helper_Social();
                    return $_this->tinyUrl($matches[0]);
                },
                $msg
            );
        }
        $url .= urlencode($msg);
        return $url;
    }


    /**
     * Create a Tweet button.
     *
     * @param array $params
     * @return string
     * @see http://twitter.com/about/resources/tweetbutton
     */
    public function tweetButton(array $params = array()) {
        $params = new Garp_Util_Configuration($params);
        $params->setDefault('url', null)
            ->setDefault('text', null)
            ->setDefault('via', null)
            ->setDefault('related', null)
            ->setDefault('lang', null)
            ->setDefault('count', 'horizontal')
            ->setDefault('loadScript', true);

        // set required parameters
        $attributes = array(
            'class'      => 'twitter-share-button',
            'data-count' => $params['count']
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
     *
     * @param string $title
     * @param string $body
     * @param int $categoryId
     * @param int $rating
     * @return string
     */
    public function hyvesTipUrl($title, $body, $categoryId = 12, $rating = 5) {

        $url = 'http://www.hyves-share.nl/button/tip/?tipcategoryid=%s&rating=%s&title=%s&body=%s';
        $title = $title;
        $body = $body;
        return sprintf($url, $categoryId, $rating, $title, $body);
    }

    /**
     * This method needs to be run at about the end of the HTML BODY tag.
     * It fires up all necessaries for Facebook's SDK, which is required for XFBML tags.
     * Also, the HTML tag itself should have
     * the xmlns:fb="http://www.facebook.com/2008/fbml" attribute.
     *
     * @return void
     */
    public function facebookInit() {
        $this->_needsFacebookInit = true;
        if (!$appId = $this->facebookAppId()) {
            throw new Exception(
                'Please fill out auth.adapters.facebook.appId with the id you'
                . ' retrieve from your friendly system administrator.'
            );
        }
        $channelUrl = $this->facebookChannelUrl();

        return $this->view->partial(
            'partials/social/facebook/init.phtml', 'g', array(
                'appId' => $appId,
                'channelUrl' => $channelUrl
            )
        );
    }

    /**
     * Get Facebook App Id
     *
     * @return string
     */
    public function facebookAppId() {
        return $this->view->config()->auth->adapters->facebook->appId;
    }

    /**
     * Get Facebook channel URL
     *
     * @return string
     */
    public function facebookChannelUrl() {
        return $this->view->fullUrl("/js/garp/social/facebook/channel.php");
    }

    /**
     * Generate a Facebook share URL
     *
     * @param string $url Defaults to $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
     * @param string $text
     * @return string
     */
    public function facebookShareUrl($url = null, $text = null) {
        $shareUrl = is_null($url) ? $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] : $url;
        $url = 'http://facebook.com/sharer.php?u=' . urlencode($shareUrl);
        if (!is_null($text)) {
            $url .= '&t=' . $text;
        }
        return $url;
    }

    /**
     * Generate a Facebook like button
     *
     * @param array $params Various Facebook URL parameters
     * @param bool $useFacebookPageAsUrl
     * @return string
     */
    public function facebookLikeButton(array $params = array(), $useFacebookPageAsUrl = false) {
        $this->_needsFacebookInit = true;
        $params = new Garp_Util_Configuration($params);
        $params->setDefault(
            'href',
            array_key_exists('href', $params) && $params['href'] ?
                $params['href'] :
                $this->_getCurrentUrl()
        )
            ->setDefault('layout', 'button_count')
            ->setDefault('show_faces', 'false')
            ->setDefault('width', 450)
            ->setDefault('action', 'like')
            ->setDefault('font', 'lucida grande')
            ->setDefault('colorscheme', 'light');

        if ($useFacebookPageAsUrl) {
            $this->_setFacebookPageUrlAsHref($params);
        }

        $html = '<fb:like ' . $this->_renderHtmlAttribs($params) . '></fb:like>';
        return $html;
    }

    /**
     * Generate a Facebook recommend button (which is a like button,
     * but with a different action / label)
     *
     * @param array $params Various Facebook URL parameters
     * @param bool $useFacebookPageAsUrl
     * @return string
     */
    public function facebookRecommendButton(
        array $params = array(), $useFacebookPageAsUrl = false
    ) {
        $this->_needsFacebookInit = true;
        $params['action'] = 'recommend';
        return $this->facebookLikeButton($params, $useFacebookPageAsUrl);
    }

    /**
     * Display Facebook comments widget
     *
     * @param array $params Various Facebook URL parameters
     * @return string
     */
    public function facebookComments(array $params = array()) {
        $this->_needsFacebookInit = true;
        $params = new Garp_Util_Configuration($params);
        $params->setDefault(
            'href',
            array_key_exists('href', $params) && $params['href'] ?
                $params['href'] :
                $this->view->fullUrl($this->view->url())
        )
            ->setDefault('width', 400) /* Minimum recommended width: 400 */
            ->setDefault('num_posts', 10)
            ->setDefault('colorscheme', 'light');

        $html = '<fb:comments ' . $this->_renderHtmlAttribs($params) . '></fb:comments>';
        return $html;
    }

    /**
     * Generate a Facebook facepile.
     *
     * @param array $params Various Facebook URL parameters
     * @param bool $useFacebookPageAsUrl
     * @return string
     */
    public function facebookFacepile(array $params = array(), $useFacebookPageAsUrl = false) {
        $this->_needsFacebookInit = true;
        $params = new Garp_Util_Configuration($params);
        $params->setDefault(
            'href', array_key_exists('href', $params) && $params['href'] ?
                $params['href'] :
                $this->_getCurrentUrl()
        )
            ->setDefault('max_rows', 1)
            ->setDefault('width', 450)
            ->setDefault('colorscheme', 'light');

        if ($useFacebookPageAsUrl) {
            $this->_setFacebookPageUrlAsHref($params);
        }

        $html = '<fb:facepile ' . $this->_renderHtmlAttribs($params) . '></fb:facepile>';
        return $html;
    }

    /**
     * Print Facebook Open Graph tags.
     *
     * @param array $ogData The Open Graph information
     * @return string The HTML
     */
    public function facebookOgData(array $ogData = array()) {
        $html = '';
        $metaTemplate = '<meta property="%s" content="%s">';
        $ini = Zend_Registry::get('config');

        if (!array_key_exists('admins', $ogData)) {
            if ($ini->auth->adapters->facebook->admins) {
                $ogData['admins'] = $ini->auth->adapters->facebook->admins;
            } else {
                throw new Exception(
                    'The auth.adapters.facebook.admins configuration parameter is missing. ' .
                    'Please use a numeric id, not a username.'
                );
            }
        }

        if (!array_key_exists('app_id', $ogData)) {
            if ($ini->auth->adapters->facebook->appId) {
                $ogData['app_id'] = $ini->auth->adapters->facebook->appId;
            }
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
        if (empty($ogData['title'])) {
            if ($this->view->title) {
                $title = $this->view->title;
            } else if (!empty($this->view->config()->app->name)) {
                $title = $this->view->config()->app->name;
            }

            if ($title) {
                $ogData['title'] = $title;
            }
        }

        if (empty($ogData['image']) && !empty($this->view->config()->app->image)) {
            if (basename($this->view->config()->app->image) === $this->view->config()->app->image) {
                $ogData['image'] = $this->view->image()->getUrl($this->view->config()->app->image);
            } else {
                $ogData['image'] = $this->view->fullUrl($this->view->config()->app->image);
            }
        }
        if (empty($ogData['description'])) {
            if ($this->view->description) {
                $ogData['description'] = __($this->view->description);
            } elseif (!empty($this->view->config()->app->description)) {
                $ogData['description'] = __($this->view->config()->app->description);
            }
        }
        if (empty($ogData['locale']) && !empty($this->view->config()->app->locale)) {
            $ogData['locale'] = $this->view->config()->app->locale;
        }

        if (!empty($this->view->config()->app->name)) {
            $ogData['site_name'] = $this->view->config()->app->name;
        }

        foreach ($ogData as $ogKey => $ogValue) {
            $prefix = in_array($ogKey, array('admins', 'app_id')) ? 'fb' : 'og';
            $metaHtml = sprintf(
                $metaTemplate,
                $this->view->escape($prefix . ':' . $ogKey),
                $this->view->escape($ogValue)
            );
            $html .= "$metaHtml\n";
        }
        return $html;
    }

    /**
     * Generate a LinkedIn share button
     *
     * @param array $params
     * @return string
     * @see http://www.linkedin.com/publishers
     */
    public function linkedinShareButton(array $params = array()) {
        $html = '<script type="in/share" ';
        if (!empty($params['url'])) {
            $html .= 'data-url="' . $this->view->escape($params['url']) . '" ';
        }
        if (!empty($params['counter'])) {
            $html .= 'data-counter="' . $this->view->escape($params['counter']) . '" ';
        }
        $html .= '></script>';

        // Add the LinkedIn Javascript to the stack
        // Must be rendered in the view using "$this->script()->render()"
        $this->view->script()->src('http://platform.linkedin.com/in.js');
        return $html;
    }

    /**
     * Shorten a URL with TinyURL
     *
     * @param string $url
     * @return string
     */
    public function tinyUrl($url) {
        $tinyurl = file_get_contents('http://tinyurl.com/api-create.php?url=' . $url);
        return $tinyurl;
    }

    /**
     * Wether the current instance uses a Facebook plugin that requires Facebook init.
     *
     * @return boolean
     */
    public function needsFacebookInit() {
        return $this->_needsFacebookInit;
    }

    /**
     * Returns current url, stripped of any possible url queries.
     *
     * @return string
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
     *
     * @param Garp_Util_Configuration $attribs The configuration object
     * @return string
     */
    protected function _renderHtmlAttribs(Garp_Util_Configuration $attribs) {
        $attributePairs = array();
        foreach ($attribs as $attribName => $attribValue) {
            $attributesPairs[] = $attribName . '="' . $attribValue . '"';
        }
        return implode(' ', $attributesPairs);
    }

    /**
     * Modify params by making the organization's Facebook page the href.
     *
     * @param Garp_Util_Configuration $params
     * @return void
     */
    protected function _setFacebookPageUrlAsHref(Garp_Util_Configuration $params) {
        $ini = Zend_Registry::get('config');
        if (!$ini->organization->facebook) {
            throw new Exception("Missing url: organization.facebook in application.ini");
        }
        $params['href'] = $ini->organization->facebook;
    }

}

