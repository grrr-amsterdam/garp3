<?php
/**
 * Garp_Content_Export_Abstract
 * Blueprint for content exporters
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
abstract class Garp_Content_Export_Abstract {
	/**
	 * File extension
	 * @var String
	 */
	protected $_extension = '';
	
	
	/**
	 * Return the bytes representing the export format (for instance, binary code 
	 * describing a PDF or Excel file). These will be offered to download.
	 * @param Garp_Util_Configuration $params Various parameters describing which content to export
	 * @return String
	 */
	public function getOutput(Garp_Util_Configuration $params) {
		$filter = array();
		if (array_key_exists('filter', $params) && $params['filter']) {
			$filter = urldecode($params['filter']);
			$filter = Zend_Json::decode($params['filter']);
		}
		$fetchOptions = array('query' => $filter);

		if (!empty($params['fields'])) {
			$fields = explode(',', $params['fields']);
			$fetchOptions['fields'] = array_combine($fields, $fields);
		}

		if (isset($params['sortField']) && isset($params['sortDir'])) {
			$fetchOptions['sort'] = array($params['sortField'].' '.$params['sortDir']);
		}
			
		switch ($params['selection']) {
			case 'id':
				// specific record
				$params->obligate('id');
				$fetchOptions['query']['id'] = Zend_Json::decode($params['id']);
			break;
			case 'page':
				$params->obligate('pageSize');
				// specific page
				if (isset($params['page'])) {
					$fetchOptions['start'] = (($params['page']-1)*$params['pageSize']);
					$fetchOptions['limit'] = $params['pageSize'];
				// specific selection of pages
				} elseif (isset($params['from']) && isset($params['to'])) {
					$pages = ($params['to'] - $params['from'])+1;
					$fetchOptions['start'] = (($params['from']-1)*$params['pageSize']);
					$fetchOptions['limit'] = ($pages*$params['pageSize']);
				} else {
					throw new Garp_Content_Exception('Invalid paging configuration given. Possible options: "page", "from", "to".');
				}
			break;
		}

		$fetchOptions['filterForeignKeys'] = true;

		$className = Garp_Content_Api::modelAliasToClass($params['model']);
		$model = new $className;
		$this->_bindModels($model);

		$mem = new Garp_Util_Memory();
		$mem->useHighMemory();

		$manager = new Garp_Content_Manager($model);
		$data = $manager->fetch($fetchOptions);
		$data = (array)$data;
		if (empty($data)) {
			$data = array(
				array('message' => 'Geen resultaten gevonden.')
			);
		}
		$humanizedData = $this->_humanizeData($data, $model);
		$formattedData = $this->_format($model, $humanizedData);
		return $formattedData;
	}
	
	
	/**
	 * Generate a filename for the exported text file
	 * @param Garp_Util_Configuration $params
	 * @return String 
	 */
	public function getFilename(Garp_Util_Configuration $params) {
		$className = Garp_Content_Api::modelAliasToClass($params['model']);
		$model = new $className();
		$filename  = $model->getName();
		$filename .= '_'.date('Y_m_d');
		$filename .= '.';
		$filename .= $this->_extension;
		return $filename;
	}
	
	
	/**
	 * Format a recordset
	 * @param String $model The exported model. Formatters may want additional metadata from this.
	 * @param Array $rowset
	 * @return String
	 */
	abstract protected function _format(Garp_Model $model, array $rowset);


	/**
 	 * Translate the columns of a record into the human-friendly versions used 
 	 * in the CMS
 	 * @param Array $data
 	 * @param Garp_Model_Db $model
 	 * @return Array
 	 */
	protected function _humanizeData($data, Garp_Model_Db $model) {
		$humanizedData = array();
		
		foreach ($data as $i => $datum) {
			foreach ($datum as $column => $value) {
				$field = $model->getFieldConfiguration($column);
				if ($field['type'] === 'checkbox') {
					$value = $value ? __('yes') : __('no');
				}
				$alias = $column;
				if ($field) {
					$alias = $field['label'];
				}

				$alias = __($alias);
				if (is_array($value)) {
					// OMG recursion!
					$value = $this->_humanizeData($value, $model);
				}
				$humanizedData[$i][$alias] = $value;
			}
		}
		return $humanizedData;
	}


	/**
 	 * Bind all HABTM related models so they, too, get exported
 	 * @param Garp_Model_Db $model
 	 * @return Void
 	 */
	protected function _bindModels(Garp_Model_Db $model) {
		// Add HABTM related records
		$relations = $model->getConfiguration('relations');
		foreach ($relations as $key => $config) {
			if ($config['type'] !== 'hasAndBelongsToMany' && $config['type'] !== 'hasMany') {
				continue;
			}
			$otherModelName = 'Model_'.$config['model'];
			$otherModel = new $otherModelName();
			$bindingModel = null;
			if ($config['type'] === 'hasAndBelongsToMany') {
				$bindingModel = $model->getBindingModel($otherModel);
				$otherModelAlias = 'm';
			} else {
				$otherModelAlias = $otherModel->getName();
			}
			$options = array(
				'bindingModel' => $bindingModel,
				'modelClass' => $otherModel,
				'conditions' => $otherModel->select()->from(
					array($otherModelAlias => $otherModel->getName()),
					array($config['label'] => $otherModel->getRecordLabelSql($otherModelAlias))
				)
			);
			$model->bindModel($config['label'], $options);
		}
	}
}
