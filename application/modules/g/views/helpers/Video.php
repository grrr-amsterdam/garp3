<?php
/**
 * G_View_Helper_Video
 * Generic video helper. Can render YouTube and Vimeo.
 *
 * @package G_View_Helper
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class G_View_Helper_Video extends Zend_View_Helper_Abstract {
    /**
     * Render a video player.
     *
     * @param Garp_Db_Table_Row $video A record from a video table
     * @param array $options Various rendering options
     * @return mixed
     */
    public function video($video = null, array $options = array()) {
        if (!func_num_args()) {
            // provide fluent interface
            return $this;
        }
        if (!$video) {
            throw new InvalidArgumentException(
                __METHOD__ . ' expects parameter 1 to be Garp_Db_Table_Row'
            );
        }
        return $this->render($video, $options);
    }

    /**
     * Render a video player.
     *
     * @param Garp_Db_Table_Row $video A record from a video table
     * @param array $options Various rendering options
     * @return mixed
     */
    public function render($video, $options = array()) {
        $helper = $this->_getSpecializedHelper($video);
        return $helper->render($video, $options);
    }

    /**
     * Get only a player URL. Some sensible default parameters will be applied.
     *
     * @param Garp_Db_Table_Row $video A record from a video table
     * @param array $options Various rendering options
     * @return string
     */
    public function getPlayerUrl($video, $options = array()) {
        $helper = $this->_getSpecializedHelper($video);
        return $helper->getPlayerUrl($video, $options);
    }

    /**
     * Check if video is Vimeo
     *
     * @param Garp_Db_Table_Row|string|array $video
     * @return bool
     */
    public function isVimeo($video) {
        $playerurl = (is_string($video) ? $video :
            (isset($video['player']) ? $video['player'] : $video));
        return preg_match('~player\.vimeo\.com~i', $playerurl);
    }

    /**
     * Check if video is Youtube
     *
     * @param Garp_Db_Table_Row|string|array $video
     * @return bool
     */
    public function isYoutube($video) {
        $playerurl = (is_string($video) ? $video :
            (isset($video['player']) ? $video['player'] : ''));
        return preg_match('~youtube\.com~i', $playerurl);
    }

    /**
     * Return either the Vimeo or YouTube helper
     *
     * @param Garp_Db_Table_Row|string|array $video
     * @return Zend_View_Helper_Abstract
     */
    protected function _getSpecializedHelper($video) {
        if ($this->isVimeo($video)) {
            return $this->view->vimeo();
        } elseif ($this->isYoutube($video)) {
            return $this->view->youTube();
        }
        throw new Exception('Unsupported media type detected: ' . $video);
    }

}
