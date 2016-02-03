<?php
/**
 * Move unilingual content to multilingual tables in case of i18n models.
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage MySql
 */
class Garp_Spawn_MySql_I18nForker {
	const ERROR_CANT_CREATE_TABLE =
		"Unable to create the %s table.";

	/**
	 * @var Garp_Spawn_Model_Base $_model
	 */
	protected $_model;

	/**
	 * @var Garp_Spawn_MySql_Table_Abstract $_source
	 */
	protected $_source;

	/**
	 * @var Garp_Spawn_MySql_Table_Abstract $_target
	 */
	protected $_target;

	/**
 	 * @var Garp_Cli_Protocol
 	 */
	protected $_feedback;


	public function __construct(Garp_Spawn_Model_Base $model, Garp_Cli_Ui_Protocol $feedback = null) {
		$this->setFeedback($feedback);

		$tableFactory = new Garp_Spawn_MySql_Table_Factory($model);

		$this->setModel($model);
		$source = $tableFactory->produceConfigTable();
		$target = $tableFactory->produceLiveTable();

		$this->setSource($source);
		$this->setTarget($target);

		$this->_createTableIfNotExists();

		$sql = $this->_renderContentMigrationSql();
		$this->_executeSql($sql);
	}

	public function setFeedback(Garp_Cli_Ui_Protocol $feedback) {
		$this->_feedback = $feedback;
	}

	public function getFeedback() {
		return $this->_feedback;
	}

	/**
	 * @return Garp_Spawn_MySql_Table_Abstract
	 */
	public function getTarget() {
		return $this->_target;
	}

	/**
	 * @param Garp_Spawn_MySql_Table_Abstract $target
	 */
	public function setTarget($target) {
		$this->_target = $target;
	}

	/**
	 * @return Garp_Spawn_MySql_Table_Abstract
	 */
	public function getSource() {
		return $this->_source;
	}

	/**
	 * @param Garp_Spawn_MySql_Table_Abstract $source
	 */
	public function setSource($source) {
		$this->_source = $source;
	}

	/**
	 * @return Garp_Spawn_Model_Base
	 */
	public function getModel() {
		return $this->_model;
	}

	/**
	 * @param Garp_Spawn_Model_Base $model
	 */
	public function setModel($model) {
		$this->_model = $model;
	}

	protected function _executeSql($sql) {
		$adapter = Zend_Db_Table::getDefaultAdapter();
		return $adapter->query($sql);
	}

	protected function _createTableIfNotExists() {
		$tableFactory = new Garp_Spawn_MySql_Table_Factory($this->getModel()->getI18nModel());
		$table        = $tableFactory->produceConfigTable();


		if (!Garp_Spawn_MySql_Table_Base::exists($table->name)) {
			$table->create();
		} else {
			// Make sure an existing table is updated
			$baseSynchronizer = new Garp_Spawn_MySql_Table_Synchronizer(
				$this->getModel()->getI18nModel(), $this->getFeedback());
			$baseSynchronizer->sync(false);
		}

		/*
		if (
			!Garp_Spawn_MySql_Table_Base::exists($table->name) &&
			!$table->create()
		) {
			$error = sprintf(self::ERROR_CANT_CREATE_TABLE, $table->name);
			throw new Exception($error);
		}
		 */

	}

	protected function _renderContentMigrationSql() {
		$target				= $this->getTarget();
		$i18nTableName		= strtolower($target->name . Garp_Spawn_Config_Model_I18n::I18N_MODEL_ID_POSTFIX);
		$model 				= $this->getModel();
		$relationColumnName	= Garp_Spawn_Relation_Set::getRelationColumn($model->id);

		$language         = $this->_getDefaultLanguage();
		$fieldNames       = $this->_getMultilingualFieldNames();
		$existingColumns = $this->getOverlappingColumnsFromBase($fieldNames);
		if (!count($existingColumns)) {
			return '';
		}
		$fieldNamesString = implode(',', $existingColumns);

		if (!$this->_tableHasRecords($i18nTableName)) {
			$statement =
				"INSERT IGNORE INTO `{$i18nTableName}` ({$relationColumnName}, lang, {$fieldNamesString}) "
				."SELECT id, '{$language}', {$fieldNamesString} "
				."FROM `{$target->name}`"
			;
		} else {
			$sqlSetStatements = implode(',', $this->_getSqlSetStatementsForUpdate(
				$target->name, $i18nTableName, $existingColumns));
			$statement = "UPDATE `{$i18nTableName}` " .
				"INNER JOIN `{$target->name}` ON `{$i18nTableName}`.`{$relationColumnName}` = " .
				"`{$target->name}`.`id` " .
				"SET {$sqlSetStatements} WHERE " .
				"`{$i18nTableName}`.`{$relationColumnName}` = `{$target->name}`.`id` AND " .
				"`lang` = '{$language}'";
		}
		return $statement;
	}

	protected function _tableHasRecords($tableName) {
		$stmt = Zend_Db_Table::getDefaultAdapter()->query(
			"SELECT COUNT(*) AS `count` FROM {$tableName}");
		$rows = $stmt->fetchAll();
		if (!isset($rows[0]['count'])) {
			return;
		}
		$count = $rows[0]['count'];
		return intval($count) > 0;
	}

	protected function _getSqlSetStatementsForUpdate($fromTable, $toTable, $columns) {
		return array_map(function($col) use ($fromTable, $toTable) {
			return "`$toTable`.`$col` = `$fromTable`.`$col`";
		}, $columns);
	}

	protected function getOverlappingColumnsFromBase($multilingualColumns) {
		return array_values(array_intersect($multilingualColumns, array_map(function($col) {
			return $col->name;
		}, $this->getTarget()->getColumns())));
	}

	protected function _getDefaultLanguage() {
		$ini = Zend_Registry::get('config');
		$defaultLanguage = $ini->resources->locale->default;

		if (!$defaultLanguage) {
			throw new Exception("resources.locale.default should be set in application.ini");
		}

		return $defaultLanguage;
	}

	protected function _getMultilingualFieldNames() {
		return array_merge(
			array_map(function($field) {
				return $field->name;
			}, $this->getModel()->fields->getFields('multilingual', true)),
 			array_map(function($rel) {
				return $rel->column;	;
			}, $this->getModel()->relations->getRelations('multilingual', true))
		);
	}

}
