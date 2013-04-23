<?php
/**
 * A representation of a translated MySQL view.
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Model
 * @group Spawn
 */
class Garp_Model_Spawn_MySql_View_I18n extends Garp_Model_Spawn_MySql_View_Abstract {
	const TRANSLATED_TABLE_POSTFIX = '_i18n';

	public function getName() {
		// return $this->getModelId() . self::POSTFIX;
	}
	
	public static function deleteAll() {
		// parent::deleteAllByPostfix(self::POSTFIX);
		/**
		 * @todo
		*/
	}
	
	public function renderSql() {
		$model 		= $this->getModel();
		$locales 	= Garp_I18n::getAllPossibleLocales();

		if (!$model->isMultiLingual()) {
			return;
		}

		$statements = array();
		foreach ($locales as $locale) {
			$statements[] = $this->_renderSqlForLang($locale);
		}

		$sql 		= implode("\n", $statements);
		$output 	= $this->_renderCreateView($sql);
		return $output;
	}
	
	protected function _renderSqlForLang($locale) {
		$model 				= $this->getModel();
		$modelId 			= $this->getModelId();
		$unilingualFields 	= $model->fields->getFields('multilingual', false);
		$multilingualFields = $model->fields->getFields('multilingual', true);
		$defaultLocale		= Garp_I18n::getDefaultLocale();

		$sql = 'SELECT ';

		$unilingualFieldRefs = array();
		foreach ($unilingualFields as $field) {
			$unilingualFieldRefs[] = $field->name;
		}
		$sql .= implode(', ', $unilingualFieldRefs) . ', ';

		$multilingualFieldRefs = array();
		foreach ($multilingualFields as $field) {
			$multilingualFieldRefs[] = $locale === $defaultLocale ?
				"{$modelId}_{$locale}.{$field->name} AS {$field->name}" :
				"COALESCE({$modelId}_{$locale}.{$field->name}, {$modelId}_{$defaultLocale}.{$field->name}) AS {$field->name}"
			;
		}
		$sql .= implode(', ', $multilingualFieldRefs) . ' ';

		$sql .= 'FROM ' . $modelId;		
		$sql .= $this->_renderJoinForLocale($locale);
		
		if ($locale !== $defaultLocale) {
			$sql .= $this->_renderJoinForLocale($defaultLocale);
		}
		
		return $sql;

		// 
		// /* Language neutral columns */
		// id, created, modified,
		// /* Translatable columns */
		// COALESCE(ai_de.name, ai_en.name) AS name,
		// COALESCE(ai_de.description, ai_en.description) AS description
		// 
		// FROM animals a
		// 
		// LEFT OUTER JOIN animals_i18n ai_de ON ai_de.animal_id = a.id AND ai_de.lang = 'DE'
		// LEFT OUTER JOIN animals_i18n ai_en ON ai_en.animal_id = a.id AND ai_en.lang = 'EN'

		
	}
	
	protected function _getViewLocaleAlias($locale) {
		$modelId . '_' . $locale;
	}
	
	protected function _renderJoinForLocale($locale) {
		$modelId			= $this->getModelId();
		$translatedTable 	= $modelId . self::TRANSLATED_TABLE_POSTFIX;
		$aliasForLocale 	= $modelId . '_' . $locale;
		$parentColumn 		= Garp_Util_String::camelcasedToUnderscored($this->getModel()->id) . '_id';
		$sql = "LEFT OUTER JOIN {$translatedTable} {$aliasForLocale} ON {$aliasForLocale}.{$parentColumn} = {$modelId}.id AND {$aliasForLocale}.lang = '{$locale}' ";
	}
}