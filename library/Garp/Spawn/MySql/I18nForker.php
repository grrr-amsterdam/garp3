<?php
/**
 * Move unilingual content to multilingual tables in case of i18n models.
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage MySql
 */
class Garp_Spawn_MySql_I18nForker {
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
	

	public function __construct(Garp_Spawn_Model_Base $model) {
		$tableFactory 	= new Garp_Spawn_MySql_Table_Factory($model);
		$source 		= $tableFactory->produceConfigTable();
		$target 		= $tableFactory->produceLiveTable();

		$this->setModel($model);
		$this->setSource($source);
		$this->setTarget($target);
		
		$sql = $this->_renderContentMigrationSql();
		$this->_executeSql($sql);
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
	
	protected function _renderContentMigrationSql() {
		$target				= $this->getTarget();
		// @fixme Validate this patch by Harmen. Maybe a more elegant solution is possible?
		$i18nTableName		= strtolower($target->name . Garp_Spawn_Config_Model_I18n::I18N_MODEL_ID_POSTFIX);
		$model 				= $this->getModel();
		$relationColumnName	= Garp_Spawn_Relation_Set::getRelationColumn($model->id);

		$language			= $this->_getDefaultLanguage();
		$fieldNamesString	= $this->_getMultilingualFieldNamesString();
		
		$statement = 
			"INSERT IGNORE INTO `{$i18nTableName}` ({$relationColumnName}, lang, {$fieldNamesString}) "
			."SELECT id, '{$language}', {$fieldNamesString} "
			."FROM `{$target->name}`"
		;

		return $statement;
	}
	
	protected function _getDefaultLanguage() {
		$ini = Zend_Registry::get('config');
		$defaultLanguage = $ini->resources->locale->default;
		
		if (!$defaultLanguage) {
			throw new Exception("resources.locale.default should be set in application.ini");
		}
		
		return $defaultLanguage;
	}
	
	protected function _getMultilingualFieldNamesString() {
		$model 				= $this->getModel();
		$multilingualFields = $model->fields->getFields('multilingual', true);
		$fieldNames			= array();
		
		foreach ($multilingualFields as $field) {
			$fieldNames[] = $field->name;
		}
		
		$fieldNamesString = implode(', ', $fieldNames);
		return $fieldNamesString;
	}
	
}
