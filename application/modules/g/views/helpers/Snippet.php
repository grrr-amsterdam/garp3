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

		$snippetModel = new G_Model_Snippet();
		$snippet = $snippetModel->fetchByIdentifier($identifier);
		
		if (
			array_key_exists('render', $params) &&
			!$params['render']
		) {
			return $snippet;
		} else return $this->render($snippet, $partial, $params);
	}
	

	/**
	 * Returns a specific field without rendering any partials or magical Snippet data.
	 */
	public function getField($identifier, $fieldName) {
		$snippetModel = new G_Model_Snippet();
		$snippet = $snippetModel->fetchByIdentifier($identifier);

		if (isset($snippet->{$fieldName})) {
			return $snippet->{$fieldName};
		} else throw new Exception('Snippet '.$snippet->id." does not have a $fieldName field.");
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

		//$output = "<!--//garp-snippet//".$snippet->id." -->";
		$output .= $this->view->partial($partial, $module, $params);
		return $output;
	}
}