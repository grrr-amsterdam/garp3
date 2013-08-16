<?php
/**
 * Garp_Cli_Command_CreateI18nView
 * Create an SQL view for i18n tables.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cli
 * @lastmodified $Date: $
 */
class Garp_Cli_Command_CreateI18nView extends Garp_Cli_Command {
	/**
	 * Central start method
	 * @param Array $args Various options. Must contain;
	 * [1]	String	Table name
	 * [2]	String  Locale to generate view for (defaults to all)
	 * @return Void
	 */
	public function main(array $args = array()) {
		if (empty($args[0])) {
			Garp_Cli::errorOut('Insufficient arguments.');
			Garp_Cli::errorOut('Usage: php garp.php CreateI18nView <table-name> [<locale> <locale2> <localeN>...]');
		} else {
			$table = $args[0];
			$locales = array_slice($args, 0);
			if (empty($locales)) {
				$locales = Garp_I18n::getAllPossibleLocales();
			}
			
			foreach ($locales as $locale) {
				$this->_createView($table, $locale);
			}
		}
	}
	
	
	/**
	 * Create view for $table in $locale
	 * @param String $table
	 * @param String $locale
	 * @return Void
	 */
	protected function _createView($table, $locale) {
		$viewName = $table.'_'.$locale;
		$table = new Zend_Db_Table($table);
		$columns = $table->info(Zend_Db_Table::COLS);
		$adapter = $table->getAdapter();
		$sqlTable = $adapter->quoteIdentifier($table->info(Zend_Db_Table::NAME));
		$viewName = $adapter->quoteIdentifier($viewName);
		$viewColumns = array();
				
		foreach ($columns as $column) {
			$sqlColumn = $adapter->quoteIdentifier($column);
			if (preg_match('/_'.$locale.'$/i', $column)) {
				$viewColumns[] = $sqlColumn.' AS '.$adapter->quoteIdentifier(preg_replace('/_'.$locale.'$/i', '', $column));
			} else {
				$viewColumns[] = $sqlColumn;
			}
		}
		$viewColumns = implode(',', $viewColumns);
		
		$dropSql	= "DROP VIEW IF EXISTS $viewName";
		$createSql	= "CREATE VIEW $viewName AS SELECT $viewColumns FROM $sqlTable";
		if ($adapter->query($dropSql) && $adapter->query($createSql)) {
			Garp_Cli::lineOut('Successfully created view '.$viewName);
		} else {
			Garp_Cli::errorOut('Could not create view '.$viewName);
		}
	}
}
