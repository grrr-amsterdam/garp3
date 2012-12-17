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
 	 * @return String
 	 */
	public function render(array $chapter) {
		$partial = 'partials/chapters/'.($chapter['type'] ?: 'default').'.phtml';
		return $this->view->partial($partial, 'default', array('content' => $chapter['content']));
	}


	/**
 	 * Render a content node
 	 * @param Array $contentNode
 	 * @return String
 	 */
	public function renderContentNode(array $contentNode) {
		$model = strtolower($contentNode['model']);
		$type  = $contentNode['type'] ?: 'default';
		$partial = "partials/chapters/$model/$type.phtml";
		return $this->view->partial($partial, 'default', array('contentNode' => $contentNode));
	}
}

/**
		foreach ($chapter['content'] as $contentNode) {
			// test for datatype
			$out .= '<div class="grid-'.$contentNode['columns'].'-6">';
			if ($contentNode['model'] == 'Image') {
				$out .= $this->view->partial('partials/chapters/image.phtml', 'default', array('image' => $contentNode['data']));
			} elseif ($contentNode['model'] == 'Text') {
				$out .= $this->view->partial('partials/chapters/text.phtml', 'default', array('text' => $contentNode['data']));
			}
			$out .= '</div>';
		}
		$out .= '</div>';
		return $out;
 */
