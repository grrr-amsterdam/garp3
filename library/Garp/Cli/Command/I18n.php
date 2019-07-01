<?php

use Garp\Functional as f;

/**
 * Garp_Cli_Command_I18n
 * Perform various internationalization-related tasks.
 *
 * @package Garp_Cli_Command
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Cli_Command_I18n extends Garp_Cli_Command {

    /**
     * This updates your database to be compliant with the refactored Translatable behavior.
     * I18n views no longer use fallbacks to the default language records.
     * Therefore an update to existing databases is necessary. This command populates records in
     * non-default languages to provide the data that used to be fallen back on.
     *
     * @param array $args
     * @return bool
     */
    public function populateLocalizedRecords(array $args = array()) {
        Zend_Registry::get('CacheFrontend')->setOption('caching', false);
        $mem = new Garp_Util_Memory();
        $mem->useHighMemory();

        $models = !empty($args) ? array($args[0]) : $this->_getInternationalizedModels();
        array_walk($models, array($this, '_populateRecordsForModel'));

        Zend_Registry::get('CacheFrontend')->setOption('caching', true);
        Garp_Cache_Manager::purge();

        Garp_Cli::lineOut('Done.');
        return true;
    }

    protected function _populateRecordsForModel($modelName) {
        Garp_Cli::lineOut("Updating model " . $modelName, Garp_Cli::YELLOW);
        $model = $this->_getModel($modelName);
        $i18nModel = $model->getObserver('Translatable')->getI18nModel($model);
        $foreignKeyColumns = $this->_getForeignKeyColumns($i18nModel, $model);

        // Trigger an update on the record and let Translatable behavior do the work
        $records = $this->_fetchRecordsInDefaultLanguage($model);
        foreach ($records as $record) {
            $foreignKeyWhereClause = $this->_getForeignKeyWhereClause(
                $record, $model,
                $foreignKeyColumns
            );

            $fkValue = $record[$foreignKeyColumns[0]];
            unset($record[$foreignKeyColumns[0]]);

            $updateData = array_map(
                function ($value) {
                    return array(Garp_I18n::getDefaultLocale() => $value);
                }, $record->toArray()
            );
            unset($updateData['slug']);

            // Bit of a hack but if we only select multilingual fields Translatable will strip 'em
            // out and leave an empty UPDATE statement. Put id in there to give UPDATE statement
            // some body.
            $updateData['id'] = $fkValue;

            // Update with existing data. Translatable should pick up and populate other-language
            // records where applicable.
            try {
                $model->update(
                    $updateData,
                    $foreignKeyWhereClause
                );
            } catch (Garp_Model_Validator_Exception $e) {
                Garp_Cli::errorOut(
                    'Could not update record ' . $updateData['id'] .
                    ' because of exception:'
                );
                Garp_Cli::errorOut($e->getMessage());
            }
        }
        Garp_Cli::lineOut('Done.');
    }

    protected function _getForeignKeyWhereClause(Garp_Db_Table_Row $record, $model,
        $foreignKeyColumns
    ) {
        $whereData = f\pick($foreignKeyColumns, $record->toArray());
        // Assume one "id" primary key
        if (count($foreignKeyColumns) > 1) {
            throw new Exception("Can't deal with multiple foreign keys right now!");
        }
        $whereData = array_combine(array('id'), array_values($whereData));
        return $model->arrayToWhereClause($whereData);
    }

    protected function _fetchRecordsInDefaultLanguage(Garp_Model_Db $model) {
        $i18nColumns = array_filter(
            $model->getConfiguration('fields'), function ($col) {
                return $col['multilingual'];
            }
        );
        $i18nColumns = array_map(
            function ($col) {
                return $col['name'];
            }, $i18nColumns
        );
        $i18nModel = $model->getObserver('Translatable')->getI18nModel($model);
        $foreignKeyColumns = $this->_getForeignKeyColumns($i18nModel, $model);
        return $i18nModel->fetchAll(
            $i18nModel->select()
                ->from($i18nModel->getName(), array_merge($i18nColumns, $foreignKeyColumns))
                ->where('lang = ?', Garp_I18n::getDefaultLocale())
        );
    }

    protected function _getForeignKeyColumns(Garp_Model_Db $i18nModel, Garp_Model_Db $model) {
        $reference = $i18nModel->getReference(get_class($model));
        $foreignKeyColumns = $reference['columns'];
        return $foreignKeyColumns;
    }

    protected function _getInternationalizedModels() {
        $modelSet = (array)Garp_Spawn_Model_Set::getInstance();
        $modelsWithI18nFields = array_filter(
            $modelSet, function ($model) {
                return array_reduce(
                    $model->fields->toArray(), function ($hasMultilingualField, $field) {
                        return $hasMultilingualField || $field->multilingual;
                    }, false
                );
            }
        );
        return array_values(
            array_map(
                function ($model) {
                    return $model->id;
                }, $modelsWithI18nFields
            )
        );
    }

    protected function _getModel($modelName) {
        $modelName = "Model_{$modelName}";
        $model = new $modelName;
        $unwantedObservers = array_filter(
            array_keys($model->getObservers()), function ($observer) {
                return !in_array($observer, array('Translatable'));
            }
        );
        foreach ($unwantedObservers as $observer) {
            $model->unregisterObserver($observer);
        }
        return $model;
    }
}
