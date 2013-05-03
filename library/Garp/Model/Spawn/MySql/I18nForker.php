<?php
/**
 * Move unilingual content to multilingual tables in case of i18n models.
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage MySql
 */
class Garp_Model_Spawn_MySql_I18nForker {
	/**
	 * @var Garp_Model_Spawn_Model $_model
	 */
	protected $_model;

	/**
	 * @var Garp_Model_Spawn_MySql_Table_Abstract $_source
	 */
	protected $_source;	
	
	/**
	 * @var Garp_Model_Spawn_MySql_Table_Abstract $_target
	 */
	protected $_target;
	

	public function __construct(Garp_Model_Spawn_Model $model) {
		$tableFactory 	= new Garp_Model_Spawn_MySql_Table_Factory();
		$source 		= $tableFactory->produceConfigTable($model);
		$target 		= $tableFactory->produceLiveTable($model);

		$this->setModel($model);
		$this->setSource($source);
		$this->setTarget($target);
		
		$sql = $this->_renderContentMigrationSql();
		$this->_executeSql($sql);
	}
	
	/**
	 * @return Garp_Model_Spawn_MySql_Table_Abstract
	 */
	public function getTarget() {
		return $this->_target;
	}
	
	/**
	 * @param Garp_Model_Spawn_MySql_Table_Abstract $target
	 */
	public function setTarget($target) {
		$this->_target = $target;
	}
	
	/**
	 * @return Garp_Model_Spawn_MySql_Table_Abstract
	 */
	public function getSource() {
		return $this->_source;
	}
	
	/**
	 * @param Garp_Model_Spawn_MySql_Table_Abstract $source
	 */
	public function setSource($source) {
		$this->_source = $source;
	}
	
	/**
	 * @return Garp_Model_Spawn_Model
	 */
	public function getModel() {
		return $this->_model;
	}
	
	/**
	 * @param Garp_Model_Spawn_Model $model
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
		$i18nTableName		= $target->name . Garp_Model_Spawn_Config_Model_I18n::I18N_MODEL_ID_POSTFIX;
		$model 				= $this->getModel();
		$relationColumnName	= Garp_Model_Spawn_Relations::getRelationColumn($model->id);

		$language			= $this->_getDefaultLanguage();
		$fieldNamesString	= $this->_getMultilingualFieldNamesString();
		
		$statement = 
			"INSERT INTO `{$i18nTableName}` ({$relationColumnName}, lang, {$fieldNamesString}) "
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