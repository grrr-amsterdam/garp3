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
 	 * Alias used for the model binding in beforeFetch
 	 * @var String
 	 */
	const I18N_MODEL_BINDING_ALIAS = 'translation';

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
		if (empty($config['columns'])) {
			throw new Garp_Model_Behavior_Exception('"columns" is a required key.');
		}
		$this->_translatableFields = $config['columns'];
	}

	/**
 	 * An article is nothing without its Chapters. Before every fetch
 	 * we make sure the chapters are fetched right along, at least in 
 	 * the CMS.
 	 * @param Array $args Event listener parameters
 	 * @return Void
 	 */
	public function beforeFetch(&$args) {
		$model = &$args[0];
		$select = &$args[1];

		$isCms = Zend_Registry::isRegistered('CMS') && Zend_Registry::get('CMS');
		if ($isCms) {
			if ($where = $select->getPart(Zend_Db_Select::WHERE)) {
				foreach ($where as $clause) {
					// Check if it's a search query
					if (stripos($clause, 'like') !== false) {
						preg_match('/%.*?%/', $clause, $matches);
						if (!empty($matches[0])) {
							$this->_joinCmsSearchQuery($model, $select, $matches[0]);
							break;
						}
					}
				}
			}
			$this->bindWithI18nModel($model);
		}
	}	

	/**
 	 * A real hacky solution to enable admins to search for translated content in the CMS
 	 */
	protected function _joinCmsSearchQuery(Garp_Model_Db $model, Zend_Db_Select &$select, $likeValue) {
		$languages = Garp_I18n::getAllPossibleLocales();
		foreach ($languages as $language) {
			$factory = new Garp_I18n_ModelFactory($language);
			$translatedViewModel = $factory->getModel($model);
			$i18nModel = $this->getI18nModel($model);
			$referenceMap = $i18nModel->getReference(get_class($model));
			$adapter = $model->getAdapter();

			$translatedFields = $this->_getSuffixedColumnNames($language);
			$translatedFieldsSql = array();
			foreach ($translatedFields as $i18nField => $field) {
				$translatedFieldsSql[] = $field.' AS '.$i18nField;
				$select->orWhere($i18nField.' LIKE ?', $likeValue);
			}
			$translatedFieldsSql = implode(',', $translatedFieldsSql);
			$select->join(
				array($translatedViewModel->getName() => new Zend_Db_Expr('(SELECT id AS id_'.$language.', '.$translatedFieldsSql.' FROM '.$translatedViewModel->getName().')')),
				$adapter->quoteIdentifier($translatedViewModel->getName()) . '.id_'.$language.' = '.$adapter->quoteIdentifier($model->getJointView()) . '.id',
				array()
			);
		}
	}

	/**
 	 * Get translated fields with a language suffix ("name_nl")
 	 * @param String $language
 	 * @return Array
 	 */
	protected function _getSuffixedColumnNames($language) {
		$out = array();
		foreach ($this->_translatableFields as $field) {
			$out[$field.'_'.$language] = $field;
		}
		return $out;
	}

	/**
 	 * After fetch callback
 	 * @param Array $args
 	 * @return Void 
 	 */
	public function afterFetch(&$args) {
		$model   = &$args[0];
		$results = &$args[1];
		$select  = &$args[2];
		// In the CMS environment, the translated data is merged into the parent data
		$isCms = Zend_Registry::isRegistered('CMS') && Zend_Registry::get('CMS');
		if ($isCms) {
			$iterator = new Garp_Db_Table_Rowset_Iterator($results, array($this, 'mergeTranslatedFields'));
			$iterator->walk();
		}
	}

	/**
 	 * Merge translated fields into the main records
 	 * @return Void
 	 */
	public function mergeTranslatedFields($result) {
		if (isset($result->{self::I18N_MODEL_BINDING_ALIAS})) {
			$translationRecordList = $result->{self::I18N_MODEL_BINDING_ALIAS}->toArray();
			$translatedFields = array();
			$allLocales = Garp_I18n::getAllPossibleLocales();
			foreach ($this->_translatableFields as $translatableField) {
				// provide default values
				foreach ($allLocales as $locale) {
					$translatedFields[$translatableField][$locale] = null;
				}
				foreach ($translationRecordList as $translationRecord) {
					$lang = $translationRecord[self::LANG_COLUMN];
					$translatedFields[$translatableField][$lang] = $translationRecord[$translatableField];
				}
			}
			// We now have a $translatedFields array like this:
			// array(
			//   "name" => array(
			//     "nl" => "Schaap",
			//     "en" => "Sheep"
			//   )
			// )
			$result->setFromArray($translatedFields);
			unset($result->{self::I18N_MODEL_BINDING_ALIAS});
		}
	}

	/**
 	 * Callback before inserting or updating.
 	 * Extracts translatable fields.
 	 * @param Array $data The submitted data
 	 * @return Void
 	 */
	protected function _beforeSave(&$data) {
		foreach ($this->_translatableFields as $field) {
			if (array_key_exists($field, $data)) {
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
			// Reset queue
			$this->_queue = array();
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
			if (array_key_exists($language, $value)) {
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
		if (!$row->save()) {
			throw new Garp_Model_Behavior_Exception('Cannot save i18n record in language "'.$language.'"');
		}
		return true;
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
 	 * Bind with i18n model
 	 * @param Garp_Model_Db $model
 	 * @return Void
 	 */
	public function bindWithI18nModel(Garp_Model_Db $model) {
		$i18nModel = $this->getI18nModel($model);
		$model->bindModel(self::I18N_MODEL_BINDING_ALIAS, array(
			'modelClass' => $i18nModel,
			'conditions' => $i18nModel->select()->from(
				$i18nModel->getName(), 
				array_merge($this->_translatableFields, array(self::LANG_COLUMN))
			)
		));
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
