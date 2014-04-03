<?php
/**
 * Generate and alter tables to reflect base models and association models
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Model
 */
class Garp_Model_Spawn_MySql_Manager {
	/** @param Array $_models Array of Garp_Model_Spawn_Model objects */
	protected $_models = array();
	protected $_adapter;


	public function __construct(Array $models) {
		$this->_models = $models;
		$this->_adapter = Zend_Db_Table::getDefaultAdapter();
		$this->_adapter->query('SET NAMES utf8;');
		// $this->_checkPrivileges();

		//	Manage model table
		foreach ($models as $model) {
			echo "{$model->id}\n";
			$sqlFromConfig = Garp_Model_Spawn_MySql_Table::renderCreateFromSpawnModel($model);
			$newTable = new Garp_Model_Spawn_MySql_Table($sqlFromConfig);

			if (Garp_Model_Spawn_MySql_Table::exists($model->id)) {
				$sqlFromLive = Garp_Model_Spawn_MySql_Table::renderCreateFromLiveTable($model->id);
				$existingTable = new Garp_Model_Spawn_MySql_Table($sqlFromLive);
				$newTable->compareWithExisting($existingTable);
			} else {
				if ($newTable->create())
					p("√ Table generated.");
				else throw new Exception("Unable to create the {$model->id} table.");
			}

			//	Manage binding model tables
			$habtmRelations = $model->relations->getRelations('type', 'hasAndBelongsToMany');
			if ($habtmRelations) {
				foreach ($habtmRelations as $rel) {
					$bindingModelName = Garp_Model_Spawn_Relations::getBindingModelName($model->id, $rel->model);
					$bindingModelTableName = Garp_Model_Spawn_MySql_Table::getBindingModelTableName($bindingModelName);
					$sqlFromConfig = Garp_Model_Spawn_MySql_Table::renderCreateForBindingModel($model->id, $rel->model);
					$newTable = new Garp_Model_Spawn_MySql_Table($sqlFromConfig);

					if (Garp_Model_Spawn_MySql_Table::exists($bindingModelTableName)) {
						$sqlFromLive = Garp_Model_Spawn_MySql_Table::renderCreateFromLiveTable($bindingModelTableName);
						$existingTable = new Garp_Model_Spawn_MySql_Table($sqlFromLive);
						$newTable->compareWithExisting($existingTable, true);
					} else {
						if ($newTable->create())
							p("√ Binding model table generated.");
						else throw new Exception("Unable to create the {$bindingModelName} binding model table.");
					}
				}
			}

			p();
		}
	}


	protected function _checkPrivileges() {
		$dbConfig = $this->_adapter->getConfig();
		$tableName = '_garp_spawn_probing_privileges';

		p("Database permissions · Probing", false);
		try {
			$this->_adapter->query("CREATE TABLE {$tableName} (id INTEGER NOT NULL, PRIMARY KEY (id));");
			$this->_adapter->query("ALTER TABLE {$tableName} MODIFY id bigint(20) not null default 0;");
			$this->_adapter->query("DROP TABLE {$tableName};");
			p("√ Sufficient rights; preparing changes.");
		} catch (Exception $e) {
			throw new Exception(
				"It seems you do not have the necessary permissions to generate database tables. Make sure user '{$dbConfig['username']}' can CREATE, ALTER and DROP in the '{$dbConfig['dbname']}' database."
				."\n\n"
				.$e->getMessage()
			);
		}
	}
}