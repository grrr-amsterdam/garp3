<?php
/**
 * G_View_Helper_Snippet
 * Render snippets.
 * @author Harmen Janssen, David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class G_View_Helper_Snippet extends Zend_View_Helper_Abstract {
	/**
	 * Render a snippet.
	 * If no arguments are given, $this is returned 
	 * so there can be some chaining.
	 * @param String $identifier
	 * @param String $partial Path to the partial that renders this snippet.
	 * @param Array $params Optional extra parameters to pass to the partial. Set 'render' to false to return only the data, without any Snippet functionality.
	 * @return String
	 */
	public function snippet($identifier = false, $partial = false, array $params = array()) {
		if (!func_num_args()) {
			return $this;
		}

		$snippetModel = $this->_getSnippetModel();
		$snippet = $snippetModel->fetchByIdentifier($identifier);
		
		if (
			array_key_exists('render', $params) &&
			!$params['render']
		) {
			return $snippet;
		}
		return $this->render($snippet, $partial, $params);
	}
	
	/**
	 * Returns a specific field without rendering any partials or magical Snippet data.
	 */
	public function getField($identifier, $fieldName) {
		$snippetModel = $this->_getSnippetModel();
		$snippet = $snippetModel->fetchByIdentifier($identifier);

		if (isset($snippet->{$fieldName})) {
			return $snippet->{$fieldName};
		}
		throw new Exception('Snippet '.$snippet->id." does not have a $fieldName field.");
	}

	/**
	 * Render the snippet
	 * @param String $identifier
	 * @param String $partial Path to the partial that renders this snippet.
	 * @param Array $params Optional extra parameters to pass to the partial.
	 * @return String
	 */
	public function render(Zend_Db_Table_Row_Abstract $snippet, $partial = false, array $params = array()) {
		$module 			= $partial ? 'default' : 'g';
		$partial			= $partial ?: 'partials/snippet.phtml';
		$params['snippet']  = $snippet;

		$output = "<!--//garp-snippet//".$snippet->id." -->";
		$output .= $this->view->partial($partial, $module, $params);
		return $output;
	}

	/**
 	 * Return a snippet model.
 	 * If the Translatable behavior is registered, load the model thru the Garp_I18n_ModelFactory.
 	 * This returns the Snippet model based on a translated MySQL view.
 	 * @return Model_Snippet
 	 */
	protected function _getSnippetModel() {
		$snippetModel = new Model_Snippet();
		if ($snippetModel->getObserver('Translatable')) {
			$i18nModelFactory = new Garp_I18n_ModelFactory();
			$snippetModel = $i18nModelFactory->getModel($snippetModel);
		}
		$snippetModel->bindModel('Image', array(
			'modelClass' => 'Model_Image'
		));
		return $snippetModel;
	}
}
