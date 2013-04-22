<?php
/**
 * Garp_Model_Behavior_Translatable
 * Makes it easy to save content in different languages.
 * Allows for the following:
 *
 * array(
 *   "name" => array(
 *     "en" => "the quick brown fox",
 *     "nl" => "de snelle bruine vos"
 *   )
 * )
 *
 * The translated content will be extracted into an i18n record.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Garp_Model_Behavior
 */
class Garp_Model_Behavior_Translatable extends Garp_Model_Behavior_Abstract {
	/**
 	 * Name of the column that stores the language in i18n tables.
 	 * @var String
 	 */
	const LANG_COLUMN = 'lang';

	/**
 	 * Suffix for i18n tables
 	 * @var String
 	 */
	const I18N_TABLE_SUFFIX = '_i18n';

	/**
 	 * The columns that can be translated
 	 * @var Array
 	 */
	protected $_translatableFields;

	/**
 	 * Stores translatable fields from beforeSave til afterSave
 	 * @var Array
 	 */
	protected $_queue = array();

	/**
 	 * Configure this behavior
 	 * @param Array $config
 	 * @return Void
 	 */
	protected function _setup($config) {
		$this->_translatableFields = $config;
	}

	/**
 	 * Callback before inserting or updating.
 	 * Extracts translatable fields.
 	 * @param Array $data The submitted data
 	 * @return Void
 	 */
	protected function _beforeSave($data) {
		foreach ($this->_translatableFields as $field) {
			if (!empty($data[$field])) {
				$this->_queue[$field] = $data[$field];
				unset($data[$field]);
			}
		}
	}

	/**
 	 * Callback after inserting or updating.
 	 * @param Garp_Model_Db $model
 	 * @param Array $primaryKeys 
 	 */
	protected function _afterSave(Garp_Model_Db $model, $primaryKeys) {
		if ($this->_queue) {
			$locales = Garp_I18n::getAllPossibleLocales();
			array_walk($locales, array($this, '_saveI18nRecord'), $model, $primaryKeys);
		}
	}

	/**
 	 * Save a new i18n record in the given language
 	 * @param String $language
 	 * @param Garp_Model_Db $model
 	 * @param Array $primaryKeys 
 	 * @return Boolean
 	 */
	protected function _saveI18nRecord($language, Garp_Model_Db $model, array $primaryKeys) {
		$columns = array();
		foreach ($this->_queue as $column => $data) {
			if (!empty($data[$language])) {
				$columns[$column] = $data[$language];
			}
		}
		$columns[self::LANG_COLUMN] = $language;

		// @todo Figure out the foreign keys dynamically
		$i18nModelName = $model->getName().self::I18N_TABLE_SUFFIX;
		$primaryKeyColumns = $model->info(Zend_Db_Table_Abstract::PRIMARY);
		foreach ($primaryKeys as $i => $primaryKey) {
			if (empty($primaryKeyColumns[$i])) {
				throw new Garp_Model_Behavior_Exception('Mismatched primary key values and columns. Column #'.$i.' not found in table.');
			}
			$foreignKey = $model->getName().'_'.$primaryKeyColumns[$i];
			$columns[$foreignKey] = $primaryKey;
		}

		print "Inserting the following:\n";
		print_r($columns);
		exit;
	}

	/**
 	 * Before insert callback
 	 * @param Array $args 
 	 * @return Void
 	 */
	public function beforeInsert(&$args) {
		$model = &$args[0];
		$data  = &$args[1];
		$this->_beforeSave($data);
	}

	/**
 	 * Before update callback
 	 * @param Array $args
 	 * @return Void
 	 */
	public function beforeUpdate(&$args) {
		$model = &$args[0];
		$data  = &$args[1];
		$where = &$args[2];
		$this->_beforeSave($data);
	}

	/**
 	 * After insert callback
 	 * @param Array $args
 	 * @return Void
 	 */
	public function afterInsert(&$args) {
		$model      = &$args[0];
		$data       = &$args[1];
		$primaryKey = &$args[2];
		$this->_afterSave($model, (array)$primaryKey);
	}

	/**
 	 * After update callback
 	 * @param Array $args 
 	 * @return Void
 	 */
	public function afterUpdate(&$args) {
		$model        = &$args[0];
		$affectedRows = &$args[1];
		$data         = &$args[2];
		$where        = &$args[3];

		$pkExtractor = new Garp_Db_PrimaryKeyExtractor($model, $where);
		$pks = $pkExtractor->extract();
		$this->_afterSave($model, $pks);
	}
}
