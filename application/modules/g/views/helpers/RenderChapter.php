<?php
/**
 * Garp_View_Helper_RenderChapter
 * Helper for rendering Chapters (as used in Garp's "magazine layout").
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Garp
 * @subpackage   View
 */
class G_View_Helper_RenderChapter extends Zend_View_Helper_Abstract {
	/**
 	 * Render the chapter
 	 * @param Array $chapter
 	 * @return String
 	 */
	public function renderChapter(array $chapter) {
		$out = '<div class="chapter '.$chapter['type'].'">';
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
	}
}
