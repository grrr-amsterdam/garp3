<?php
/**
 * Garp_View_Helper_Chapter
 * Helper for rendering Chapters (as used in Garp's "magazine layout").
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Garp
 * @subpackage   View
 */
class G_View_Helper_Chapter extends Zend_View_Helper_Abstract {
	/**
 	 * For a fluent interface.
 	 * @return G_View_Helper_Chapter $this
 	 */
	public function chapter() {
		return $this;
	}


	/**
 	 * Render the chapter
 	 * @param Array $chapter
 	 * @param Array $params Additional parameters for the partial.
 	 * @return String
 	 */
	public function render(array $chapter, array $params = array()) {
		$partial = 'partials/chapters/'.($chapter['type'] ?: 'default').'.phtml';
		$params['content'] = $chapter['content'];
		return $this->view->partial($partial, 'default', $params);
	}


	/**
 	 * Render a content node
 	 * @param Array $contentNode
 	 * @param Array $params Additional parameters for the partial
 	 * @return String
 	 */
	public function renderContentNode(array $contentNode, array $params = array()) {
		$model = strtolower($contentNode['model']);
		$type  = $contentNode['type'] ?: 'default';
		$partial = "partials/chapters/$model/$type.phtml";
		$params['contentNode'] = $contentNode;
		return $this->view->partial($partial, 'default', $params);
	}
}
