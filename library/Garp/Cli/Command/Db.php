<?php
/**
 * Garp_Cli_Command_Db
 * Contains various database related methods.
 *  
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cli
 * @lastmodified $Date: $
 */
class Garp_Cli_Command_Db extends Garp_Cli_Command {
	/**
	 * Show table info (DESCRIBE query) for given table
	 * @param Array $args
	 * @return Void
	 */
	public function info(array $args = array()) {
		if (empty($args)) {
			Garp_Cli::errorOut('Insufficient arguments');
			Garp_Cli::lineOut('Usage: garp Db info <tablename>');
			return;
		}
		$db = new Zend_Db_Table($args[0]);
		print_r($db->info());
		Garp_Cli::lineOut('');
	}


	/**
	 * Walks over every text column of every record of every table 
	 * and replaces references to $subject with $replacement.
	 * Especially useful since all images in Rich Text Editors are
	 * referenced with absolute paths including the domain. This method
	 * can be used to replace "old domain" with "new domain" in one go.
	 *
	 * @param Array $args
	 * @return Void
	 */
	public function replace(array $args = array()) {
		$subject = !empty($args[0]) ? $args[0] : Garp_Cli::prompt('What is the string you wish to replace?');
		$replacement = !empty($args[1]) ? $args[1] : Garp_Cli::prompt('What is the new string you wish to insert?');
		$subject = trim($subject);
		$replacement = trim($replacement);

		$models = Garp_Content_Api::getAllModels();
		foreach ($models as $model) {
			if (is_subclass_of($model->class, 'Garp_Model_Db')) {
				$this->_replaceString($model->class, $subject, $replacement);
			}
		}
	}


	/**
	 * Replace $subject with $replacement in all textual columns of the table.
	 * @param  String  $modelClass  The model classname
	 * @param  String  $subject	    The string that is to be replaced
	 * @param  String  $replacement The string that will take its place
	 * @return Void
	 */
	protected function _replaceString($modelClass, $subject, $replacement) {
		$model = new $modelClass();
		$columns = $this->_getTextualColumns($model);
		if ($columns) {
			$adapter = $model->getAdapter();
			$updateQuery = 'UPDATE '.$adapter->quoteIdentifier($model->getName()).' SET ';
			foreach ($columns as $i => $column) {
				$updateQuery .= $adapter->quoteIdentifier($column).' = REPLACE(';
				$updateQuery .= $adapter->quoteIdentifier($column).', ';
				$updateQuery .= $adapter->quoteInto('?, ', $subject);
				$updateQuery .= $adapter->quoteInto('?)', $replacement);
				if ($i < (count($columns)-1)) {
					$updateQuery .= ',';
				}
			}
			if ($response = $adapter->query($updateQuery)) {
				$affectedRows = $response->rowCount();
				Garp_Cli::lineOut('Model: '.$model->getName());
				Garp_Cli::lineOut('Affected rows: '.$affectedRows);
				Garp_Cli::lineOut('Involved columns: '.implode(', ', $columns)."\n");
			} else {
				Garp_Cli::errorOut('Error: update for table `'.$model->getName().'` failed.');
			}
		}
	}


	/**
	 * Get all textual columns from a table
	 * @param  Garp_Model_Db  $model  The model
	 * @return Array
	 */
	protected function _getTextualColumns(Garp_Model_Db $model) {
		$columns = $model->info(Zend_Db_Table::METADATA);
		foreach ($columns as $column => $meta) {
			if (!in_array($meta['DATA_TYPE'], array('varchar', 'text', 'mediumtext', 'longtext', 'tinytext'))) {
				unset($columns[$column]);
			}
		}
		return array_keys($columns);
	}
}
