<?php
/**
 * Garp_Cli_Command_Model
 * Wrapper around model-related functionality.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cli
 * @lastmodified $Date: $
 */
class Garp_Cli_Command_Model extends Garp_Cli_Command {
	/**
	 * Create a new model file
	 * A couple of valid command calls:
	 * 
	 * $ garp Model create User users		// create php & js model User for table users
	 * $ garp Model create php User	users 	// create php model User for table users
	 * $ garp Model create					// get prompted for further information
	 * $ garp Model create php				// get prompted for further information
	 *
	 * @param Array $args
	 * @return Void
	 */
	public function create(array $args = array()) {
		$mode = 'all'; // wether to create only PHP, only JS or all model files.
		$modelName = null;
		$tableName = null;

		switch (count($args)) {
			case 0:
				$modelName = Garp_Cli::prompt('What\'s the model classname going to be? (omit the "Model_" namespace in your answer)');
				$tableName = Garp_Cli::prompt('Generate model for which table?');
			break;
			case 1:
				$mode = $args[0];
				$modelName = Garp_Cli::prompt('What\'s the model classname going to be? (omit the "Model_" namespace in your answer)');
				$tableName = Garp_Cli::prompt('Generate model for which table?');
			break;
			case 2:
				$modelName = $args[0];
				$tableName = $args[1];
			break;
			case 3:
				$mode = $args[0];
				$modelName = $args[1];
				$tableName = $args[2];
			break;
		}
		
		if (!in_array($mode, array('php', 'js', 'all'))) {
			Garp_Cli::errorOut('Invalid mode selected. Options are php, js, or all.');
			return;
		}
		
		if ($mode == 'all' || $mode == 'php') {
			$this->_createPhpFile($modelName, $tableName);
		}

		if ($mode == 'all' || $mode == 'js') {
			$this->_createJsFile($modelName, $tableName);
		}
	}

	
	/**
	 * PHP Model creation
	 */


	/**
	 * Create a PHP model file
	 * @param String $modelName
	 * @param String $tableName
	 * @return Void
	 */
	protected function _createPhpFile($modelName, $tableName) {
		$temp = new Garp_Cli_Template('modelFile.php.tpl');
		$temp->modelName = $modelName;
		$temp->tableName = $tableName;
		$temp->referenceMap = array();
		$temp->observers = $this->_createObservers($modelName, $tableName);
		
		$destination = APPLICATION_PATH.'/modules/default/models/'.$modelName.'.php';
		
		if (file_put_contents($destination, $temp->render())) {
			Garp_Cli::lineOut("Successfully created PHP model at $destination");
		} else {
			Garp_Cli::errorOut("Gasp! Errors occurred!");
			Garp_Cli::errorOut("Could not create PHP model at $destination");
		}
	}
		
	
	/**
	 * Create PHP code for some default observers in PHP model file
	 * @param String $modelName
	 * @param String $tableName
	 * @return String
	 */
	protected function _createObservers($modelName, $tableName) {
		$out = '';
		$info = $this->_getTableInfo($tableName);

		// sluggable
		if (array_key_exists('slug', $info['metadata']) &&
			array_key_exists('name', $info['metadata'])) {
			$out .= "\t\t".'$this->registerObserver(new Garp_Model_Behavior_Sluggable(array(\'baseField\' => \'name\')));'."\n";
		}
		
		// timestampable
		if ((array_key_exists('created', $info['metadata']) && $info['metadata']['created']['DATA_TYPE'] == 'datetime') ||
			(array_key_exists('modified', $info['metadata']) && $info['metadata']['modified']['DATA_TYPE'] == 'datetime')) {
			$out .= "\t\t".'$this->registerObserver(new Garp_Model_Behavior_Timestampable());'."\n";
		}
		
		// not empty
		$notEmpty = array();
		foreach ($info['metadata'] as $col => $meta) {
			if (!$meta['NULLABLE'] && !$meta['PRIMARY']) {
				$notEmpty[] = '\''.$col.'\'';
			}
		}
		if ($notEmpty) {
			$out .= "\t\t".'$this->registerObserver(new Garp_Model_Validator_NotEmpty(array('.implode(', ', $notEmpty).')));'."\n";
		}
		return $out;
	}


	/**
	 * JS Model creation
	 */


	/**
	 * Create a JS model file
	 * @param String $modelName
	 * @param String $tableName
	 * @return Void
	 */
	protected function _createJsFile($modelName, $tableName) {
		$info = $this->_getTableInfo($tableName);

		$temp = new Garp_Cli_Template('modelFile.js.tpl');
		$temp->modelName	= $modelName;
		$temp->modelIcon	= strtolower($modelName);
		$temp->defaultData	= $this->_createDefaultData($info);
		$temp->sortInfo		= $this->_createSortInfo($info);
		$temp->columnModel	= $this->_createColumnModel($info);
		$temp->formConfig	= $this->_createFormConfig($info);
		
		$destination = APPLICATION_PATH.'/../public/js/models/'.lcfirst($modelName).'.js';
		
		if (file_put_contents($destination, $temp->render())) {
			Garp_Cli::lineOut("Successfully created JS model at $destination");
		} else {
			Garp_Cli::errorOut("Gasp! Errors occurred!");
			Garp_Cli::errorOut("Could not create JS model at $destination");
		}
	}
	
	
	/**
	 * Create list of default data for all columns
	 * @param Array $info
	 * @return String
	 */
	protected function _createDefaultData($info) {
		$out = array();
		foreach ($info['metadata'] as $column => $meta) {
			if ($meta['PRIMARY'] || $meta['NULLABLE']) {
				$out[$column] = null;
			} elseif ($meta['DEFAULT']) {
				$out[$column] = $meta['DEFAULT'];
			} else {
				switch($meta['DATA_TYPE']) {
					case 'bit':
					case 'smallint':
					case 'mediumint':
					case 'int':
					case 'integer':
					case 'bigint':
					case 'real':
					case 'double':
					case 'float':
					case 'decimal':
					case 'numeric':
						$out[$column] = 0;
					break;
					case 'tinyint':
						if ($meta['LENGTH'] == 1) {
							// this is considered a checkbox and Ext needs it as (string)"0"
							$out[$column] = '0';
						} else {
							$out[$column] = 0;
						}
					break;
					case 'char':
					case 'varchar':
					case 'tinytext':
					case 'text':
					case 'mediumtext':
					case 'longtext':
						$out[$column] = '';
					break;
					default:
						// there are no further defaults, just use null
						$out[$column] = null;
					break;
				}
			}
		}
		$out = Garp_Cli_JsonFormatter::encode($out, 1, '');
		return $out;
	}
	
	
	/**
	 * Create sort info block
	 * @param Array $info
	 * @return String
	 */
	protected function _createSortInfo($info) {
		$prim = current(array_values((array)$info['primary']));
		return Garp_Cli_JsonFormatter::encode(array(
			'field' => $prim,
			'direction' => 'DESC'
		), 1, '');
	}
	
	
	/**
	 * Create the column model block
	 * @param Array $info
	 * @return String
	 */
	protected function _createColumnModel($info) {
		$out  = array();
		foreach ($info['metadata'] as $col => $meta) {
			// format the column name a little
			$colName = str_replace('_', ' ', ucfirst($col));
			$colOut = array(
				'header' => new Garp_Cli_JsonFormatter_Expr("__('$colName')"),
				'dataIndex' => $col
			);
			if (strpos($col, 'name') === false) {
				$colOut['hidden'] = true;
			}
			
			if ($meta['DATA_TYPE'] == 'datetime') {
				$colOut['renderer'] = new Garp_Cli_JsonFormatter_Expr('Garp.dateTimeRenderer');
			} elseif ($col === 'image') {
				$colOut['renderer'] = new Garp_Cli_JsonFormatter_Expr('Garp.imageRenderer');
			}
			$out[] = Garp_Cli_JsonFormatter::encode($colOut, 1, '');
		}
		$out = implode(',', $out);
		return $out;
	}
	
	
	/**
	 * Create the form config block
	 * @param Array $info 
	 * @return String
	 */
	protected function _createFormConfig($info) {
		// some constants
		$out  = array(
			'layout' => 'form',
			'defaults' => array(
				'defaultType' => 'textField'
			)
		);
		
		// items
		$items = array();
		foreach ($info['metadata'] as $col => $meta) {
			// don't show these columns, we put them in the sidebar
			if (in_array($col, array('created', 'modified'))) {
				continue;
			}

			// format the column name a little
			$colName = str_replace('_', ' ', ucfirst($col));
			$item = array(
				'name'			=> $col,
				'fieldLabel'	=> new Garp_Cli_JsonFormatter_Expr("__('$colName')"),
				'disabled'		=> $this->_colIsDisabled($meta),
				'hidden'		=> $this->_colIsHidden($meta),
				'xtype'			=> $this->_getColumnEditor($meta, $col),
			);
			
			if ($meta['LENGTH']) {
				$item['maxLength'] = $meta['LENGTH'];
			}
			
			if ($this->_getFormRenderer($col)) {
				$item['renderer'] = $this->_getFormRenderer($col);
			}
			$item['allowBlank'] = (bool)$meta['NULLABLE'];
			
			// image upload field needs a destination:
			if ($item['xtype'] == 'uploadcombo') {
				if ($col === 'image') {
					$item['uploadURL'] = new Garp_Cli_JsonFormatter_Expr("BASE + 'g/content/upload/image'");
				} else {
					$item['uploadURL'] = new Garp_Cli_JsonFormatter_Expr("BASE + 'g/content/upload'");
				}
			}

			// specific validators
			if (strpos($col, 'email') !== false) {
				$item['vtype'] = 'email';
			}

			if (strpos($col, 'url') !== false) {
				$item['vtype'] = 'url';
			}
			
			// combobox needs additional config
			if ($item['xtype'] == 'combo') {
				$store = eval('return '.str_replace('enum', 'array', $meta['DATA_TYPE']).';');
				$item['editable']		= false;
				$item['triggerAction']	= 'all';
				$item['typeAhead']		= false;
				$item['mode']			= 'local';
				$item['store']			= new Garp_Cli_JsonFormatter_Expr(Zend_Json::encode($store));
			}
			
			$items[] = Garp_Cli_JsonFormatter::encode($item, 2, '');
		}
				
		$out['items'] = new Garp_CLI_JsonFormatter_Expr('[{xtype: "fieldset", items: ['.implode(',', $items).']}]');
		$out = Garp_Cli_JsonFormatter::encode($out, 1, '');		
		return $out;
	}
	
	
	/**
	 * Check if a column must be disabled by default in the JS form config.
	 * @param Array $meta The column's meta info
	 * @return Boolean
	 */
	protected function _colIsDisabled(array $meta) {
		return in_array($meta['COLUMN_NAME'], array('id', 'slug', 'created', 'modified'));
	}
	
	
	/**
	 * Check if a column must be hidden by default in the JS form config.
	 * @param Array $meta The column's meta info
	 * @return Boolean
	 */
	protected function _colIsHidden(array $meta) {
		return in_array($meta['COLUMN_NAME'], array('id', 'slug'));
	}
	
	
	/**
	 * Get form editor for a certain column
	 * @param Array $meta The column's meta info
	 * @return String
	 */
	protected function _getColumnEditor(array $meta, $colName = '') {
		$numericTypes = array('bit', 'smallint', 'mediumint', 'int', 'integer', 
							  'bigint', 'real', 'double', 'float', 'decimal', 'numeric');
		
		if (in_array($colName, array('created', 'modified'))) {
			return 'rendereddisplayfield';
		}
		
		if ($meta['DATA_TYPE'] == 'tinyint' && $meta['LENGTH'] == 1) {
			return 'checkbox';
		} elseif ($meta['COLUMN_NAME'] == 'image') {
			return 'uploadcombo';
		} elseif ($meta['DATA_TYPE'] == 'text') {
			return 'textarea';
		} elseif ($meta['DATA_TYPE'] == 'datetime') {
			return 'xdatetime';
		} elseif ($meta['DATA_TYPE'] == 'date') {
			return 'datefield';
		} elseif ($meta['DATA_TYPE'] == 'time') {
			return 'timefield';
		} elseif (in_array($meta['DATA_TYPE'], $numericTypes)) {
			return 'numberfield';
		} elseif (substr($meta['DATA_TYPE'], 0, 4) == 'enum') {
			return 'combo';
		}
		return 'textfield';
	}
	

	/**
	 * Return the form renderer for a certain column
	 * @param String $colName
	 * @return Garp_Cli_JsonFormatter_Expr
	 */
	protected function _getFormRenderer($colName = '') {
		if (in_array($colName, array('created', 'modified'))) {
			return new Garp_Cli_JsonFormatter_Expr('Garp.dateTimeRenderer');
		}
		return false;
	}
	
	
	/**
	 * Get table info (DESCRIBE query result)
	 * @param String $tableName
	 * @return Array
	 */
	protected function _getTableInfo($tableName) {
		$db = new Zend_Db_Table($tableName);
		return $db->info();
	}
}
