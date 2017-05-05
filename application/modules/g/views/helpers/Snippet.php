<?php
/**
 * G_View_Helper_Snippet
 * Render snippets.
 *
 * @package G_View_Helper
 * @author  Harmen Janssen <harmen@grrr.nl>
 * @author  David Spreekmeester <david@grrr.nl>
 */
class G_View_Helper_Snippet extends Zend_View_Helper_Abstract {
    const EXCEPTION_MISSING_FIELD = 'Snippet %s does not have a %s field.';

    /**
     * Render a snippet.
     * If no arguments are given, $this is returned
     * so there can be some chaining.
     *
     * @param string $identifier
     * @param string $partial Path to the partial that renders this snippet.
     * @param array $params Optional extra parameters to pass to the partial.
     *                      Set 'render' to false to return only the data,
     *                      without any Snippet functionality.
     * @return string
     */
    public function snippet($identifier = false, $partial = false, array $params = array()) {
        if (!func_num_args()) {
            return $this;
        }

        $locale = !empty($params['locale']) ? $params['locale'] : null;
        $snippetModel = $this->_getSnippetModel($locale);
        $snippet = $snippetModel->fetchByIdentifier($identifier);

        if (array_key_exists('render', $params) && !$params['render']) {
            return $snippet;
        }
        return trim($this->render($snippet, $partial, $params));
    }

    /**
     * Returns a specific field without rendering any partials or magical Snippet data.
     *
     * @param string $identifier
     * @param string $fieldName
     * @return string
     */
    public function getField($identifier, $fieldName) {
        $snippetModel = $this->_getSnippetModel();
        $snippet = $snippetModel->fetchByIdentifier($identifier);

        if (!isset($snippet->{$fieldName})) {
            throw new Exception(sprintf(self::EXCEPTION_MISSING_FIELD, $snippet->id, $fieldName));
        }
        return $snippet->{$fieldName};
    }

    /**
     * Render the snippet
     *
     * @param Zend_Db_Table_Row_Abstract $snippet
     * @param string $partial Path to the partial that renders this snippet.
     * @param array $params Optional extra parameters to pass to the partial.
     * @return string
     */
    public function render(
        Zend_Db_Table_Row_Abstract $snippet, $partial = false, array $params = array()
    ) {
        $module = $partial ? 'default' : 'g';
        $partial = $partial ?: 'partials/snippet.phtml';
        $params['snippet'] = $snippet;

        return $this->view->partial($partial, $module, $params);
    }

    /**
     * Return a snippet model.
     * If the Translatable behavior is registered, load the model thru the Garp_I18n_ModelFactory.
     * This returns the Snippet model based on a translated MySQL view.
     * When a language (locale) is given, that specific language will be fetched.
     * 
     * @param string $locale
     * @return Model_Snippet
     */
    protected function _getSnippetModel($locale = null) {
        $snippetModel = new Model_Snippet();
        if ($snippetModel->getObserver('Translatable')) {
            $i18nModelFactory = new Garp_I18n_ModelFactory($locale);
            $snippetModel = $i18nModelFactory->getModel($snippetModel);
        }
        $snippetModel->bindModel(
            'Image',
            array('modelClass' => 'Model_Image')
        );
        return $snippetModel;
    }
}
