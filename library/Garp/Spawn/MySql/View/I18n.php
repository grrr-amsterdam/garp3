<?php
/**
 * A representation of a translated MySQL view.
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Model
 * @group Spawn
 */
class Garp_Spawn_MySql_View_I18n extends Garp_Spawn_MySql_View_Abstract {
	const TRANSLATED_TABLE_POSTFIX = 'i18n';

	/**
	 * @var String $_locale
	 */
	protected $_locale;


	/**
	 * @param	Garp_Spawn_Model_Base		$model
	 * @param	String							$locale
	 */
	public function __construct(Garp_Spawn_Model_Base $model, $locale) {
		$this->setLocale($locale);
		
		return parent::__construct($model);
	}
	
	/**
	 * @return String
	 */
	public function getLocale() {
		return $this->_locale;
	}
	
	/**
	 * @param String $locale
	 */
	public function setLocale($locale) {
		$this->_locale = $locale;
	}
	
	

	public function getName() {
		return $this->getTableName() . '_' . $this->getLocale();
	}
	
	public static function deleteAll() {
		$locales 	= Garp_I18n::getLocales();
		
		foreach ($locales as $locale) {
			parent::deleteAllByPostfix('_' . $locale);		
		}
	}
	
	public function renderSql() {
		$model 		= $this->getModel();

		if (!$model->isMultiLingual()) {
			return;
		}

		$sql = $this->_renderSqlForLang();
		$sql = $this->_renderCreateView($sql);

		return $sql;
	}
	
	protected function _renderSqlForLang() {		
		$model 				= $this->getModel();
		$modelId 			= $this->getTableName();
		$unilingualFields 	= $model->fields->getFields('multilingual', false);
		$multilingualFields = $model->fields->getFields('multilingual', true);
		
		$locale 			= $this->getLocale();
		$defaultLocale		= Garp_I18n::getDefaultLocale();
		$table 				= $modelId;
		$localeTable		= $table . '_' . $locale;
		$defaultLocaleTable = $table . '_' . $defaultLocale;


		$sql = 'SELECT ';

		//	Unilingual fields
		$unilingualFieldRefs = array();
		foreach ($unilingualFields as $field) {
			$unilingualFieldRefs[] = $table . '.' . $field->name . ' AS ' . $field->name;
		}
		$sql .= implode(', ', $unilingualFieldRefs) . ', ';

		//	Multilingual fields
		$multilingualFieldRefs = array();
		foreach ($multilingualFields as $field) {
			$multilingualFieldRefs[] = $locale === $defaultLocale ?
				"`{$modelId}_{$locale}`.{$field->name} AS `{$field->name}`" :
				"COALESCE(`{$modelId}_{$locale}`.`{$field->name}`, `{$modelId}_{$defaultLocale}`.`{$field->name}`) AS `{$field->name}`"
			;
		}
		$sql .= implode(', ', $multilingualFieldRefs) . ' ';

		//	Join translated tables
		$sql .= 'FROM `' . $modelId . '`';
		$sql .= $this->_renderJoinForLocale($locale);

		if ($locale !== $defaultLocale) {
			$sql .= $this->_renderJoinForLocale($defaultLocale);
		}

		return $sql;		
	}
	
	protected function _getViewLocaleAlias($locale) {
		$modelId . '_' . $locale;
	}
	
	protected function _renderJoinForLocale($locale) {
		$modelId			= $this->getModel()->id;
		$tableName			= $this->getTableName();
		$translatedTable 	= $tableName . self::TRANSLATED_TABLE_POSTFIX;
		$aliasForLocale 	= $tableName . '_' . $locale;
		$parentColumn 		= Garp_Util_String::camelcasedToUnderscored($modelId) . '_id';

		$sql 				= "LEFT OUTER JOIN `{$translatedTable}` `{$aliasForLocale}` ON "
							. "`{$aliasForLocale}`.`{$parentColumn}` = `{$tableName}`.id AND `{$aliasForLocale}`.lang = '{$locale}' ";
		return $sql;
	}
}
