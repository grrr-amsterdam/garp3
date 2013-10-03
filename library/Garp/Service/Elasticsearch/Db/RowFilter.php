<?php
/**
 * Garp_Service_Elasticsearch_Db_RowFilter
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Elasticsearch
 * @lastmodified $Date: $
 */
class Garp_Service_Elasticsearch_Db_RowFilter extends Garp_Service_Elasticsearch_Db_Abstract {

	/**
	 * @var Garp_Model_Db $_model
	 */
	protected $_model;
	

	public function __construct(Garp_Model_Db $model) {
		$this->setModel($model);
	}

	/**
	 * @param 	Garp_Db_Table_Row	$row 		Unfiltered row
	 * @param 	Array 				$columns 	Elasticsearchable column names of the base model
	 * @return 	Array 							Filtered row
	 */
	public function filter(Garp_Db_Table_Row $row, array $columns) {
		$rowArray 			= $row->toArray();
		$filteredRow 		= $this->_filterRow($rowArray, $columns);

		return $filteredRow;
	}

	/**
	 * @return Garp_Model_Db
	 */
	public function getModel() {
		return $this->_model;
	}
	
	/**
	 * @param Garp_Model_Db $model
	 */
	public function setModel($model) {
		$this->_model = $model;
		return $this;
	}

	protected function _filterRow(array $rowWithRelations, array $columns) {
		$filteredRow 	= array();
		
		foreach ($rowWithRelations as $columnName => $value) {
			if (
				!is_array($value) &&
				!in_array($columnName, $columns)
			) {
				// this is a column of the primary model that should not be indexed
				continue;
			}

			if (is_array($value)) {
				//	this is data from a related model
				$value = $this->_filterRelatedData($value, $columnName);
			}

			$filteredRow[$columnName] = $value;
		}

		return $filteredRow;
	}

	protected function _filterRelatedData(array &$data, $relationName) {
		if ($data && is_array($data) && is_array(current($data))) {
			$this->_filterRelatedRowSet($data, $relationName);
			//	this is not a row but a rowset, so walk over it.

			return $data;
		}

		return $this->_filterRelatedRow($data, $relationName);
	}

	protected function _filterRelatedRowSet(array &$data, $relationName) {
		foreach ($data as $i => $dataNode) {
			$data[$i] = $this->_filterRelatedRow($dataNode, $relationName);
		}

		return $data;
	}

	protected function _filterRelatedRow(array &$data, $relationName) {
		$modelClass 	= $this->_getModelClassFromRelationName($relationName);
		$relModel 		= new $modelClass();
		$behavior 		= $relModel->getObserver('Elasticsearchable');

		if (!$behavior) {
			return;
		}

		$columns 			= $behavior->getColumns();
		$prefixedColumns	= $this->_prefixColumnsWithRelationNamespace($relationName, $columns);

		$columnsAsKeys 		= array_flip($prefixedColumns);
		$filteredData 		= array_intersect_key($data, $columnsAsKeys);

		return $filteredData;
	}

	protected function _getModelClassFromRelationName($relationName) {
		$model 			= $this->getModel();
		$relations 		= $model->getConfiguration('relations');

		if (!array_key_exists($relationName, $relations)) {
			$error = sprintf(self::ERROR_RELATION_NOT_FOUND, $relationName, get_class($model));
			throw new Exception($error);
		}

		$namespace = $this->_getModelNamespace();
		$modelClass = $namespace . $relations[$relationName]['model'];

		return $modelClass;
	}

}