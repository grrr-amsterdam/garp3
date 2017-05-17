<?php
/**
 * Garp_Model_Behavior_Translatable
 *
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
 * Important refactor as of 5 Feb 2016:
 * Originally, content in a secondary language would fall through to the default language. Not
 * providing a translation would show this content in the default language.
 * The fallback mechanism turned out to be rather straining on the database.
 *
 * We've updated the behavior to solve the problem at write-time, rather than at read-time. If
 * translations are not provided, we will populate those records with a copy of the original default
 * language content.
 *
 * @package Garp_Model_Behavior
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Model_Behavior_Translatable extends Garp_Model_Behavior_Abstract {

    const SAVE_IN_LANG_EXCEPTION = 'Cannot save i18n record in language "%s"';
    const SAVE_FOREIGN_KEY_EXCEPTION
        = 'Not all foreign keys can be filled. I got %d primary keys for %d foreign keys.';
    const MISSING_COLUMNS_EXCEPTION = '"columns" is a required key.';

    /**
     * Name of the column that stores the language in i18n tables.
     *
     * @var String
     */
    const LANG_COLUMN = 'lang';

    /**
     * Suffix for i18n tables
     *
     * @var String
     */
    const I18N_MODEL_SUFFIX = 'I18n';

    /**
     * Alias used for the model binding in beforeFetch
     *
     * @var String
     */
    const I18N_MODEL_BINDING_ALIAS = 'translation';

    /**
     * The columns that can be translated
     *
     * @var Array
     */
    protected $_translatableFields;

    /**
     * Stores translatable fields from beforeSave til afterSave
     *
     * @var Array
     */
    protected $_queue = array();

    /**
     * Wether to force i18n type output (e.g. arrays with localized content per column)
     * Usually this only happens in the CMS, or when forced.
     */
    protected $_forceI18nOutput = false;

    public function forceI18nOutput($force) {
        $this->_forceI18nOutput = $force;
    }

    /**
     * Retrieve i18n model
     *
     * @param Garp_Model_Db $model
     * @return Garp_Model_Db
     */
    public function getI18nModel(Garp_Model_Db $model) {
        $modelName = get_class($model);
        $modelName .= self::I18N_MODEL_SUFFIX;
        $model = new $modelName;

        // Do not block unpublished items, we might not get the right record from the fetchRow()
        // call in self::_saveI18nRecord()
        if ($draftable = $model->getObserver('Draftable')) {
            $draftable->setBlockOfflineItems(false);
        }
        return $model;
    }

    /**
     * Bind with i18n model
     *
     * @param Garp_Model_Db $model
     * @return Void
     */
    public function bindWithI18nModel(Garp_Model_Db $model) {
        $i18nModel = $this->getI18nModel($model);
        $model->bindModel(
            self::I18N_MODEL_BINDING_ALIAS, array(
            'modelClass' => $i18nModel,
            'conditions' => $i18nModel->select()->from(
                $i18nModel->getName(),
                array_merge($this->_translatableFields, array(self::LANG_COLUMN))
            )
            )
        );
    }

    /**
     * An article is nothing without its Chapters. Before every fetch
     * we make sure the chapters are fetched right along, at least in
     * the CMS.
     *
     * @param Array $args Event listener parameters
     * @return Void
     */
    public function beforeFetch(&$args) {
        $model = &$args[0];
        $select = &$args[1];

        $isCms = Zend_Registry::isRegistered('CMS') && Zend_Registry::get('CMS');
        if (!$isCms && !$this->_forceI18nOutput) {
            return;
        }
        $this->_modifySearchQuery($select, $model);
        $this->bindWithI18nModel($model);
    }

    /**
     * After fetch callback
     *
     * @param Array $args
     * @return Void
     */
    public function afterFetch(&$args) {
        $model   = &$args[0];
        $results = &$args[1];
        $select  = &$args[2];
        // In the CMS environment, the translated data is merged into the parent data
        $isCms = Zend_Registry::isRegistered('CMS') && Zend_Registry::get('CMS');
        if (!$isCms && !$this->_forceI18nOutput) {
            return;
        }
        $iterator = new Garp_Db_Table_Rowset_Iterator(
            $results,
            array($this, 'mergeTranslatedFields')
        );
        $iterator->walk();
    }

    /**
     * Merge translated fields into the main records
     *
     * @param Garp_Db_Table_Row $result
     * @return void
     */
    public function mergeTranslatedFields($result) {
        if (!isset($result->{self::I18N_MODEL_BINDING_ALIAS})) {
            return;
        }
        $translationRecordList = $result->{self::I18N_MODEL_BINDING_ALIAS};
        if ($translationRecordList instanceof Zend_Db_Table_Rowset_Abstract) {
            $translationRecordList = $translationRecordList->toArray();
        }
        $translatedFields = array();
        $allLocales = Garp_I18n::getLocales();
        foreach ($this->_translatableFields as $translatableField) {
            // provide default values
            foreach ($allLocales as $locale) {
                $translatedFields[$translatableField][$locale] = null;
            }
            foreach ($translationRecordList as $translationRecord) {
                $lang = $translationRecord[self::LANG_COLUMN];
                $translatedFields[$translatableField][$lang]
                    = $translationRecord[$translatableField];
            }
            $result->setVirtual($translatableField, $translatedFields[$translatableField]);
                //$lang] = $translationRecord[$translatableField];
        }
        // We now have a $translatedFields array like this:
        // array(
        //   "name" => array(
        //     "nl" => "Schaap",
        //     "en" => "Sheep"
        //   )
        // )
        //$result->setFromArray($translatedFields);
        unset($result->{self::I18N_MODEL_BINDING_ALIAS});
    }

    /**
     * Before insert callback
     *
     * @param Array $args
     * @return Void
     */
    public function beforeInsert(&$args) {
        $model = &$args[0];
        $data  = &$args[1];
        $this->_beforeSave($model, $data);
    }

    /**
     * Before update callback
     *
     * @param Array $args
     * @return Void
     */
    public function beforeUpdate(&$args) {
        $model = &$args[0];
        $data  = &$args[1];
        $where = &$args[2];
        $this->_beforeSave($model, $data, $where);
    }

    /**
     * After insert callback
     *
     * @param Array $args
     * @return Void
     */
    public function afterInsert(&$args) {
        $model      = &$args[0];
        $data       = &$args[1];
        $primaryKey = &$args[2];
        // Normalize primary key: make it an array to be compliant with
        // tables with multiple pks...
        $primaryKey = (array)$primaryKey;
        // ...and push that into an array to be compliant with afterUpdate(),
        // which might return multiple updated records
        $pKeys = array($primaryKey);
        $this->_afterSave($model, $pKeys);
    }

    /**
     * After update callback
     *
     * @param Array $args
     * @return Void
     */
    public function afterUpdate(&$args) {
        $model        = &$args[0];
        $affectedRows = &$args[1];
        $data         = &$args[2];
        $where        = &$args[3];

        $pks = $this->_getPrimaryKeysOfAffectedRows($model, $where);
        $this->_afterSave($model, $pks);
    }

    /**
     * Callback before inserting or updating.
     * Extracts translatable fields.
     *
     * @param Garp_Model_Db $model
     * @param Array $data The submitted data
     * @param String $where WHERE clause (in case of update)
     * @return Void
     */
    protected function _beforeSave($model, &$data, $where = null) {
        $localizedData = array();
        foreach ($this->_translatableFields as $field) {
            if (array_key_exists($field, $data)) {
                $localizedData[$field] = $data[$field];
                unset($data[$field]);
            }
        }
        // Can't use values that are not arrays
        $localizedData = array_filter($localizedData, 'is_array');

        // We now have an array containing all values that are provided in one or more languages
        $languages = Garp_I18n::getLocales();
        $existingRows = array_fill_keys($languages, array());
        if (!is_null($where)) {
            $pKeys = $this->_getPrimaryKeysOfAffectedRows($model, $where);
            if (!count($pKeys)) {
                // No primary keys found in update?
                // That means there aren't any affected rows and there's nothing for us to do.
                return;
            }
            $existingRows = $this->_fetchLocalizedRows($languages, $model, $pKeys);
        }

        $defaultLanguage = Garp_I18n::getDefaultLocale();
        $languages = array_diff($languages, array($defaultLanguage));
        foreach ($localizedData as $column => $val) {
            foreach ($languages as $language) {
                if (!empty($val[$language])) {
                    // If value provided in language: good!
                    continue;
                }
                if (empty($val[$defaultLanguage])) {
                    // No default? Nothing to fall back to
                    continue;
                }
                if (!$where) {
                    // If insert, just use default value
                    $localizedData[$column][$language] = $val[$defaultLanguage];
                    continue;
                }
                // Else if update, make sure existing row isn't different from default language row
                if (!$this->_translatedVersionHasBeenChanged(
                    $existingRows[$defaultLanguage],
                    $existingRows[$language], $column
                )
                ) {
                    $localizedData[$column][$language] = $val[$defaultLanguage];
                }
            }
        }
        $this->_queue = $localizedData;
    }

    /**
     * Checks if a translation (record in non-default language) is different from the record in the
     * default language.
     *
     * @param Garp_Db_Table_Row $defaultRow
     * @param Garp_Db_Table_Row $translatedRow
     * @param string $column
     * @return bool
     */
    protected function _translatedVersionHasBeenChanged($defaultRow, $translatedRow, $column) {
        return isset($defaultRow[$column]) && isset($translatedRow[$column]) &&
            // Note, this little line below prevents a field to be emptied in another language, but
            // filled in the default language.
            // This might be a problem at some point, but on the other hand these situaties would
            // always fall through in the previous version of the i18n views. So effectively
            // nothing changes from before the refactor.
            $translatedRow[$column] &&
            $defaultRow[$column] !== $translatedRow[$column];
    }

    protected function _fetchLocalizedRows(array $languages, Garp_Model_Db $model, array $pKeys) {
        $out = array();
        foreach ($languages as $language) {
            $out[$language] = $this->_fetchLocalizedRow($language, $model, $pKeys);
        }
        return $out;
    }

    protected function _fetchLocalizedRow($language, Garp_Model_Db $model, array $pKeys) {
        $i18nModel = $this->getI18nModel($model);
        $referenceMap = $i18nModel->getReference(get_class($model));
        $foreignKeyData = $this->_getForeignKeyData($referenceMap, $pKeys);
        $whereClause = $i18nModel->arrayToWhereClause(
            array_merge($foreignKeyData, array(self::LANG_COLUMN => $language))
        );

        return $i18nModel->fetchRow($i18nModel->select()->where($whereClause));
    }

    /**
     * Callback after inserting or updating.
     *
     * @param Garp_Model_Db $model
     * @param array $pKeys
     * @return void
     */
    protected function _afterSave(Garp_Model_Db $model, $pKeys) {
        if (!$this->_queue) {
            return;
        }
        $locales = Garp_I18n::getLocales();
        foreach ($pKeys as $primaryKey) {
            foreach ($locales as $locale) {
                $this->_saveI18nRecord($locale, $model, $primaryKey);
            }
        }
        // Reset queue
        $this->_queue = array();
    }

    /**
     * Save a new i18n record in the given language
     *
     * @param String $language
     * @param Garp_Model_Db $model
     * @param Array $pKeys
     * @return Boolean
     */
    protected function _saveI18nRecord($language, Garp_Model_Db $model, array $pKeys) {
        $data = $this->_extractDataForLanguage($language, $model);
        // If no data was given in the specified language, we don't save anything
        if (empty($data)) {
            return;
        }
        // Add the language...
        $data[self::LANG_COLUMN] = $language;
        // ...and foreign keys
        $data = $this->_mergeDataWithForeignKeyColumns($data, $model, $pKeys);

        $i18nModel = $this->getI18nModel($model);
        $row = $this->_fetchLocalizedRow($language, $model, $pKeys) ?: $i18nModel->createRow();

        if (!$row->isConnected()) {
            $row->setTable($i18nModel);
        }
        $row->setFromArray($data);
        if (!$row->save()) {
            throw new Garp_Model_Behavior_Exception(
                sprintf(self::SAVE_IN_LANG_EXCEPTION, $language)
            );
        }
        return true;
    }

    protected function _mergeDataWithForeignKeyColumns(array $data, Garp_Model_Db $model, $pKeys) {
        $referenceMap = $this->getI18nModel($model)->getReference(get_class($model));
        $foreignKeyData = $this->_getForeignKeyData($referenceMap, $pKeys);
        return array_merge($data, $foreignKeyData);
    }

    /**
     * Make a regular insertable data array from the localized version we saved.
     *
     * @param string $language
     * @param Garp_Model_Db $model
     * @return array
     */
    protected function _extractDataForLanguage($language, Garp_Model_Db $model) {
        $localizedData = array_filter($this->_queue, 'is_array');
        $out = array();
        foreach ($localizedData as $key => $value) {
            if (array_key_exists($language, $value)) {
                $out[$key] = $value[$language];
            }
        }
        return $out;
    }

    /**
     * Retrieve primary keys of affected records
     *
     * @param Garp_Model_Db $model
     * @param String $where
     * @return Array
     */
    protected function _getPrimaryKeysOfAffectedRows(Garp_Model_Db $model, $where) {
        if ($draftableObserver = $model->getObserver('Draftable')) {
            // Unregister so it doesn't screw up the upcoming fetch call
            $model->unregisterObserver($draftableObserver);
        }

        $pkExtractor = new Garp_Db_PrimaryKeyExtractor($model, $where);
        $pks = $pkExtractor->extract();
        if (count($pks)) {
            return array($pks);
        }
        $rows = $model->fetchAll($where);
        $pks = array();
        foreach ($rows as $row) {
            if (!$row->isConnected()) {
                $row->setTable($model);
            }
            $pks[] = (array)$row->getPrimaryKey();
        }
        if ($draftableObserver) {
            $model->registerObserver($draftableObserver);
        }
        return $pks;
    }

    /**
     * Create array containing the foreign keys in the relationship
     * mapped to the primary keys from the save.
     *
     * @param Array $referenceMap The referenceMap describing the relationship
     * @param Arary $pKeys The given primary keys
     * @return Array
     */
    protected function _getForeignKeyData(array $referenceMap, array $pKeys) {
        $data = array();
        $foreignKeyColumns = $referenceMap['columns'];
        if (count($foreignKeyColumns) !== count($pKeys)) {
            throw new Garp_Model_Behavior_Exception(
                sprintf(
                    self::SAVE_FOREIGN_KEY_EXCEPTION,
                    count($pKeys), count($foreignKeyColumns)
                )
            );
        }
        $data = array_combine($foreignKeyColumns, $pKeys);
        return $data;
    }

    protected function _modifySearchQuery(Zend_Db_Select &$select, $model) {
        $where = $select->getPart(Zend_Db_Select::WHERE);
        if (!$where) {
            return;
        }
        $select->reset(Zend_Db_Select::WHERE);
        foreach ($where as $clause) {
            // Check if it's a search query
            if (stripos($clause, 'like') !== false) {
                preg_match('/%.*?%/', $clause, $matches);
                if (!empty($matches[0])) {
                    $clause = $this->_cleanClause($clause);
                    $clause .= ' OR ' . $this->_joinCmsSearchQuery($model, $select, $matches[0]);
                }
            }
            // re-attach clause
            $whereBoolType = $this->_determineAndOrOr($clause);
            $clause = preg_replace('/(^OR|^AND)/', '', $clause);
            $clause = $this->_cleanClause($clause);
            if ($whereBoolType === 'OR') {
                $select->orWhere($clause);
                continue;
            }
            $select->where($clause);
        }
    }

    /**
     * Determine wether a WHERE clause is AND or OR
     *
     * @param string $clause
     * @return string OR or AND
     */
    protected function _determineAndOrOr($clause) {
        return substr(trim($clause), 0, 2) === 'OR' ? 'OR' : 'AND';
    }

    /**
     * Remove parentheses and whitespace around the clause
     *
     * @param string $clause
     * @return string
     */
    protected function _cleanClause($clause) {
        $clause = trim($clause);
        while ($clause[0] === '(' && $clause[strlen($clause)-1] === ')') {
            $clause = substr($clause, 1, -1);
        }
        return $clause;
    }

    /**
     * A real hacky solution to enable admins to search for translated content in the CMS
     *
     * @param Garp_Model_Db $model
     * @param Zend_Db_Select $select
     * @param string $likeValue
     * @return string A search clause
     */
    protected function _joinCmsSearchQuery(
        Garp_Model_Db $model, Zend_Db_Select &$select, $likeValue
    ) {
        $languages = Garp_I18n::getLocales();
        $default_language = array(Garp_I18n::getDefaultLocale());
        $langColumn = self::LANG_COLUMN;
        // Exclude default language, since that's already joined in the joint view
        $languages = array_diff($languages, $default_language);
        $adapter = $model->getAdapter();
        $where = array();
        foreach ($languages as $language) {
            $i18nModel = $this->getI18nModel($model);
            $i18nAlias = $model->getName() . '_i18n_' . $language;
            $onClause = $i18nModel->refMapToOnClause(
                get_class($model), $i18nAlias,
                $model->getJointView()
            );

            // join i18n model
            $select->joinLeft(
                array($i18nAlias => $i18nModel->getName()),
                "$onClause AND {$i18nAlias}.{$langColumn} = '{$language}'",
                array()
            );

            // add WHERE clauses that search in the i18n model
            $translatedFields = $this->_translatableFields;
            foreach ($translatedFields as $i18nField) {
                $where[] = "{$i18nAlias}.{$i18nField} LIKE " . $adapter->quote($likeValue);
            }

        }
        return implode(' OR ', $where);
    }

    /**
     * Configure this behavior
     *
     * @param Array $config
     * @return Void
     */
    protected function _setup($config) {
        if (empty($config['columns'])) {
            throw new Garp_Model_Behavior_Exception(self::MISSING_COLUMNS_EXCEPTION);
        }
        $this->_translatableFields = $config['columns'];
    }

}
