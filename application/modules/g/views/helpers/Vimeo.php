<?php
/**
 * G_View_Helper_Vimeo
 * Helper for rendering embedded Vimeo players
 *
 * @package G_View_Helper
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class G_View_Helper_Vimeo extends Zend_View_Helper_HtmlElement {
    /**
     * Render a Vimeo player.
     * If no arguments are given, $this is returned
     * so there can be some chaining.
     *
     * @param Garp_Db_Table_Row $vimeo A record from the `vimeo_videos` table
     * @param array $options Various rendering options
     * @return mixed
     */
    public function vimeo($vimeo = null, array $options = array()) {
        if (!func_num_args()) {
            return $this;
        }
        return $this->render($vimeo, $options);
    }


    /**
     * Render a Vimeo object tag.
     *
     * @param Garp_Db_Table_Row $vimeo A record from the `vimeo_videos` table (or `media` view)
     * @param array $options Various rendering options
     * @return mixed
     */
    public function render($vimeo, array $options = array()) {
        $this->_setDefaultAttribs($options);
        $_attribs = $options['attribs'];
        $_attribs['width']  = $options['width'];
        $_attribs['height'] = $options['height'];

        unset($options['width']);
        unset($options['height']);
        unset($options['attribs']);

        $playerUrl = $this->getPlayerUrl($vimeo, $options);

        $_attribs['frameborder'] = 0;
        $_attribs['src'] = $playerUrl;

        $html = '<iframe' . $this->_htmlAttribs($_attribs) . '></iframe>';
        return $html;
    }

    public function getPlayerUrl($vimeo, $options = array()) {
        $this->_setDefaultQueryParams($options);
        $playerurl = (is_string($vimeo) ? $vimeo :
            (isset($vimeo['player']) ? $vimeo['player'] : ''));

        $playerurl .= '?' . http_build_query((array)$options);
        return $playerurl;
    }

    /**
     * Normalize some configuration values.
     *
     * @param array $options
     * @return array Modified options
     */
    protected function _setDefaultAttribs(&$options) {
        $options = $options instanceof Garp_Util_Configuration ?
            $options : new Garp_Util_Configuration($options);
        $options
            ->setDefault('height', isset($options['width']) ? round($options['width']*0.55) : 264)
            ->setDefault('width', 480)
            ->setDefault('attribs', array());
    }

    /**
     * Normalize some query parameters
     *
     * @param array $options
     * @return void
     * @see https://developers.google.com/youtube/player_parameters
     */
    protected function _setDefaultQueryParams(&$options) {
        $options = $options instanceof Garp_Util_Configuration
            ? $options : new Garp_Util_Configuration($options);
        $options
            ->setDefault('portrait', 0)
            ->setDefault('title', 0)
            ->setDefault('byline', 0);
    }
}
