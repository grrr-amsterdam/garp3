<?php
/**
 * Garp_View_Helper_Chapter
 * Helper for rendering Chapters (as used in Garp's "magazine layout").
 *
 * @package G_View_Helper
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class G_View_Helper_Chapter extends Zend_View_Helper_Abstract {
    /**
     * For a fluent interface.
     *
     * @return G_View_Helper_Chapter $this
     */
    public function chapter() {
        return $this;
    }

    /**
     * Render the chapter
     *
     * @param array $chapter
     * @param array $params Additional parameters for the partial.
     * @return string
     */
    public function render(array $chapter, array $params = array()) {
        $partial = 'partials/chapters/' . ($chapter['type'] ?: 'default') . '.phtml';
        $params['content'] = $chapter['content'];
        return $this->view->partial($partial, 'default', $params);
    }

    /**
     * Render a content node
     *
     * @param array $contentNode
     * @param array $params Additional parameters for the partial
     * @return string
     */
    public function renderContentNode(array $contentNode, array $params = array()) {
        if (empty($contentNode)) {
            return '';
        }
        $model = strtolower($contentNode['model']);
        $type  = $contentNode['type'] ?: 'default';
        $partial = "partials/chapters/$model/$type.phtml";
        $params['contentNode'] = $contentNode;
        return $this->view->partial($partial, 'default', $params);
    }
}
