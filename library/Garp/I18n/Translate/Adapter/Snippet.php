<?php
/**
 * Garp_I18n_Translate_Adapter_Snippet
 * Adapter for Zend_Translate. Loads translation strings from the snippet table.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1
 * @package      Garp_I18n_Translate_Adapter
 */
class Garp_I18n_Translate_Adapter_Snippet extends Zend_Translate_Adapter {
	/**
	 * Load translation data
	 *
	 * @param  string|array  $data
	 * @param  string        $locale  Locale/Language to add data for, identical with locale identifier,
	 *                                see Zend_Locale for more information
	 * @param  array         $options OPTIONAL Options to use
	 * @return array
	 */
	protected function _loadTranslationData($data, $locale, array $options = array()) {
		$i18nModelFactory = new Garp_I18n_ModelFactory($locale);
		$snippetModel = $i18nModelFactory->getModel('Snippet');

		$out = array();
		$data = $snippetModel->fetchAll(
			$snippetModel->select()
			->from($snippetModel->getName(), array(
				'identifier', 
				'text' => new Zend_Db_Expr('IF(text IS NULL, identifier, text)'),
			))
			->where('has_text = ?', 1)
			->order('identifier ASC')
		);
		$out[$locale] = $this->_reformatData($data);
		return $out;
	}

	/**
 	 * Reformat rowset into a usable array.
 	 * @param Garp_Db_Table_Rowset $data
 	 * @return Array
 	 */
	protected function _reformatData(Garp_Db_Table_Rowset $data) {
		$out = array();
		foreach ($data as $datum) {
			$out[$datum->identifier] = $datum->text;
		}
		return $out;
	}

	/**
 	 * Return the adapters name
 	 * @return String
 	 */
	public function toString() {
		return 'Snippet';
	}
}
