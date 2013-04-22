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
	const I18N_MODEL_SUFFIX = 'I18n';

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
	protected function _beforeSave(&$data) {
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
			foreach ($locales as $locale) {
				$this->_saveI18nRecord($locale, $model, $primaryKeys);
			}
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
		$data = array();
		// Filter out the values in the right language
		foreach ($this->_queue as $column => $value) {
			if (!empty($value[$language])) {
				$data[$column] = $value[$language];
			}
		}
		// Add the language
		$data[self::LANG_COLUMN] = $language;

		// Add the foreign key info to link the i18n data to the parent record
		$i18nModel = $this->getI18nModel($model);
		$referenceMap = $i18nModel->getReference(get_class($model));
		$foreignKeyData = $this->_getForeignKeyData($referenceMap, $primaryKeys);
		$data = array_merge($data, $foreignKeyData);

		// Figure out wether to insert or update
		$whereClause = $i18nModel->arrayToWhereClause(
			array_merge($foreignKeyData, array(self::LANG_COLUMN => $language))
		);

		$row = $i18nModel->fetchRow($i18nModel->select()->where($whereClause));
		if (!$row) {
			$row = $i18nModel->createRow();
		}
		$row->setFromArray($data);
		$row->save();
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

	/**
 	 * Retrieve i18n model
 	 * @param Garp_Model_Db $model
 	 * @return Garp_Model_Db
 	 */
	public function getI18nModel(Garp_Model_Db $model) {
		$modelName = get_class($model);
		$modelName .= self::I18N_MODEL_SUFFIX;
		return new $modelName;
	}

	/**
 	 * Create array containing the foreign keys in the relationship mapped to the primary keys from the save.
 	 * @param Array $referenceMap The referenceMap describing the relationship
 	 * @param Arary $primaryKeys The given primary keys
 	 * @return Array 
 	 */
	protected function _getForeignKeyData(array $referenceMap, array $primaryKeys) {
		$data = array();
		$foreignKeyColumns = $referenceMap['columns'];
		if (count($foreignKeyColumns) !== count($primaryKeys)) {
			throw new Garp_Model_Behavior_Exception('Not all foreign keys can be filled. I got '.
				count($primaryKeys).' primary keys for '.count($foreignKeyColumns).' foreign keys.');
		}
		$data = array_combine($foreignKeyColumns, $primaryKeys);
		return $data;
	}
}
