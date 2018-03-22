<?php
/**
 * A representation of a MySQL view that includes the labels of related hasOne
 * and belongsTo records.
 *
 * @package Garp_Spawn_Db_View
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Spawn_Db_View_Joint extends Garp_Spawn_Db_View_Abstract {
    const POSTFIX = '_joint';

    public function getName() {
        return $this->getTableName(false) . self::POSTFIX;
    }

    public function getTableName($localized = true) {
        return (!$localized || !$this->getModel()->isMultilingual()) ?
            parent::getTableName() :
            $this->_getTranslatedViewName();
    }

    public static function deleteAll() {
        parent::deleteAllByPostfix(self::POSTFIX);
    }

    public function renderSql() {
        $statements = array();

        $singularRelations = $this->_model->relations->getRelations(
            'type',
            array('hasOne', 'belongsTo')
        );
        if (count($singularRelations) || $this->_model->isMultilingual()) {
            $statements[] = $this->_renderSelect($singularRelations);
        }

        $sql = implode("\n", $statements);
        $sql = $this->_renderCreateView($sql);

        return $sql;
    }

    /**
     * Join the base table to localized versions of itself (containing only the non-default
     * languages)
     *
     * @return string
     */
    protected function _renderJoinsToLocalizedSelf() {
        $out = array();
        $otherLocales = array_filter(
            Garp_I18n::getLocales(),
            function ($locale) {
                return Garp_I18n::getDefaultLocale() !== $locale;
            }
        );
        $baseTableName = $this->getTableName();
        foreach ($otherLocales as $locale) {
            $view = strtolower($this->_model->id) . '_' . $locale;
            $out[] = "LEFT JOIN `{$view}` ON `{$view}`.`id` = `{$baseTableName}`.`id`";
        }
        return implode("\n", $out);
    }

    protected function _getOtherTableName($modelName) {
        $model = $this->_getModelFromModelName($modelName);

        if ($model->isMultilingual()) {
            return $this->_getTranslatedViewName($model);
        }

        $factory = new Garp_Spawn_Db_Table_Factory($model);
        $table = $factory->produceConfigTable();

        return $table->name;
    }

    /**
     * @param string $modelName
     * @return Garp_Spawn_Model_Abstract
     */
    protected function _getModelFromModelName($modelName) {
        $modelSet = Garp_Spawn_Model_Set::getInstance();
        return $modelSet[$modelName];
    }

    protected function _getTranslatedViewName(Garp_Spawn_Model_Abstract $model = null) {
        if (!$model) {
            $model = $this->getModel();
        }

        $locale   = Garp_I18n::getDefaultLocale();
        $i18nView = new Garp_Spawn_Db_View_I18n($model, $locale);
        $viewName = $i18nView->getName();

        return $viewName;
    }

    protected function _renderSelect(array $singularRelations) {
        $model     = $this->getModel();
        $tableName = $this->getTableName();

        $select = "SELECT `{$tableName}`.*,\n";

        $relNodes = array();
        foreach ($singularRelations as $relName => $rel) {
            if ($rel->multilingual) {
                // Generate entry per language
                $relNodes = array_merge(
                    $relNodes,
                    $this->_getRecordLabelSqlForMultilingualRelation($relName, $rel)
                );
                continue;
            }
            $relNodes[] = $this->getRecordLabelSqlForRelation($relName, $rel);
        }

        $select .= implode(",\n", $relNodes);
        $select .= "\nFROM `{$tableName}`";

        if ($this->_model->isMultilingual()) {
            $select .= $this->_renderJoinsToLocalizedSelf();
        }

        $config = Zend_Registry::get('config');
        if ($config->spawn && $config->spawn->use_new_joint_view) {
            return $select;
        }
        foreach ($singularRelations as $relName => $rel) {
            $select .= $this->_getJoinStatement($tableName, $relName, $rel);
        }
        return $select;
    }

    protected function _getJoinStatement($tableName, $relName, $rel) {
        if ($rel->multilingual) {
            $modelId = $this->_model->id;
            $otherTableName = $this->_getOtherTableName($rel->model);
            return implode(
                "\n", array_map(
                    function ($lang) use ($relName, $rel, $modelId,
                        $otherTableName
                    ) {
                        $tableName = strtolower($modelId . '_' . $lang);
                        $localizedViewName = strtolower($relName) . '_' . $lang;
                        return "\nLEFT JOIN `{$otherTableName}` AS `{$localizedViewName}` ON " .
                        "`{$tableName}`.`{$rel->column}` = `{$localizedViewName}`.`id`";
                    }, Garp_I18n::getLocales()
                )
            );
        }
        $lcRelName    = strtolower($relName);
        $relTableName = $this->_getOtherTableName($rel->model);
        return "\nLEFT JOIN `{$relTableName}` AS `{$lcRelName}` ON " .
            "`{$tableName}`.`{$rel->column}` = `{$lcRelName}`.`id`";
    }

    protected function _getRecordLabelSqlForMultilingualRelation($relName, $rel) {
        $self = $this;
        return array_map(
            function ($lang) use ($self, $relName, $rel) {
                return $self->getRecordLabelSqlForRelation($relName, $rel, $lang);
            }, Garp_I18n::getLocales()
        );
    }

    public function getRecordLabelSqlForRelation($relationName, $relation, $locale = null) {
        $tableAlias = strtolower($relationName);
        if ($locale) {
            $tableAlias = "{$tableAlias}_{$locale}";
        }

        $config = Zend_Registry::get('config');
        if ($config->spawn && $config->spawn->use_new_joint_view) {
            $relTableName = $this->_getOtherTableName($relation->model);
            $tableName = $this->getTableName();

            // Create subquery for every relation
            $out  = "(SELECT ";
            $out .= $this->_getRecordLabelSqlForModel($tableAlias, $relation->model) .
                " AS `{$tableAlias}`";
            $out .= " FROM `{$relTableName}` AS `{$tableAlias}` WHERE ";
            $out .= "`{$tableName}`.`{$relation->column}` = `{$tableAlias}`.`id`)";
            $out .= " AS `{$tableAlias}`";
            return $out;
        }

        $sql = $this->_getRecordLabelSqlForModel($tableAlias, $relation->model) .
            " AS `{$tableAlias}`";

        return $sql;
    }

    /**
     * Compose the method to fetch composite columns as a string in a MySql query
     * to use as a label to identify the record. These have to be columns in the provided table,
     * to be able to be used flexibly in another query.
     *
     * @param string $tableAlias
     * @param string $modelName
     * @return string
     */
    protected function _getRecordLabelSqlForModel($tableAlias, $modelName) {
        $model = $this->_getModelFromModelName($modelName);

        $tableName = $this->_getOtherTableName($modelName);
        $recordLabelFieldDefs = $this->_getRecordLabelFieldDefinitions($tableAlias, $model);

        $labelColumnsListSql = implode(', ', $recordLabelFieldDefs);
        $glue = $this->_modelHasFirstAndLastNameListFields($model) ? ' ' : ', ';
        $sql = "CONVERT(CONCAT_WS('{$glue}', " . $labelColumnsListSql . ') USING utf8)';

        return $sql;
    }

    /**
     * @param String $tableAlias The alias used to refer to this table, i.e. the relation name
     * @param Garp_Spawn_Model_Abstract $model
     * @return array
     */
    protected function _getRecordLabelFieldDefinitions(
        $tableAlias, Garp_Spawn_Model_Abstract $model
    ) {
        $listFieldNames = $model->fields->getListFieldNames();
        $addFieldLabelDefinition = $this->_createAddFieldLabelDefinitionFn($tableAlias);
        $fieldDefs = array_map($addFieldLabelDefinition, $listFieldNames);

        return $fieldDefs;
    }

    /**
     * Creates closure for callback
     *
     * @param string $tableAlias
     * @return callable
     */
    protected function _createAddFieldLabelDefinitionFn($tableAlias) {
        return function ($columnName) use ($tableAlias) {
            return "IF(`{$tableAlias}`.`{$columnName}` <> \"\", " .
                "`{$tableAlias}`.`{$columnName}`, NULL)";
        };
    }

    protected function _modelHasFirstAndLastNameListFields(
        Garp_Spawn_Model_Abstract $model = null
    ) {
        if (!$model) {
            $model = $this->getModel();
        }

        try {
            return
                $model->fields->getField('first_name') &&
                $model->fields->getField('last_name');
        } catch (Exception $e) {
        }

        return false;
    }

}
