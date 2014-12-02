<?php
/**
 * Garp_Browsebox_Filter_Related
 * Filter by HABTM related models.
 * This filter is only to applicable to HABTM relations, because in other cases you can
 * just add a condition like 'WHERE user_id = ?'.
 *
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Browsebox
 * @lastmodified $Date: $
 */
class Garp_Browsebox_Filter_Related extends Garp_Browsebox_Filter_Abstract {
	/**
 	 * Parameters used by the filter
 	 * @var Array
 	 */
	protected $_params;


	/**
 	 * Class constructor
 	 * @param String $id
 	 * @param Array $params
 	 * @return Void
 	 */
	public function __construct($id, array $config = array()) {
		// validate given config options
		$config = new Garp_Util_Configuration($config);
		$config->obligate('model')
			->setDefault('bindingOptions', array())
			;

		if (empty($config['bindingOptions']['bindingModel'])) {
			throw new Garp_Browsebox_Exception('The related filter is only applicable to HABTM relationships and therefore a bindingModel must be configured.');
		}
		
		$config = (array)$config;
		parent::__construct($id, $config);
	}


	/**
 	 * Setup the filter
 	 * @param Array $params
 	 * @return Void
 	 */
	public function init(array $params = array()) {
		$model = new $this->_config['model'];
		if (count($params) != count($model->info(Zend_Db_Table_Abstract::PRIMARY))) {
			throw new Garp_Browsebox_Exception('Not enough data given. We need a value for every column in the primary key.');
		}
		$this->_params = $params;
	}


	/**
	 * Fetch results
	 * @param Zend_Db_Select $select The specific select for this instance.
	 * @param Garp_Browsebox $browsebox The browsebox, made available to fetch metadata from.
	 * @return Void
	 */
	public function fetchResults(Zend_Db_Select $select, Garp_Browsebox $browsebox) {
		if (!empty($this->_params)) {
			$model = new $this->_config['model']();
			$bindingOptions = $this->_config['bindingOptions'];
			$bindingOptions['modelClass'] = get_class($browsebox->getModel());
			$bindingOptions['conditions'] = $select;

			$model->bindModel('__related__', $bindingOptions);
			$record = $model->find($this->_params)->current();
			$model->unbindModel('__related__');

			return $record->__related__;
		} else {
			throw new Garp_Browsebox_Filter_Exception_NotApplicable();
		}
	}


	/**
	 * Fetch max amount of chunks.
	 * @param Zend_Db_Select $select The specific select for this instance.
	 * @param Garp_Browsebox $browsebox The browsebox, made available to fetch metadata from.
	 * @return Void
	 */
	public function fetchMaxChunks(Zend_Db_Select $select, Garp_Browsebox $browsebox) {
		if (!empty($this->_params)) {
			$model = $browsebox->getModel();
			$filterModel = new $this->_config['model']();
			$bindingModel = new $this->_config['bindingOptions']['bindingModel']();
			$rule1 = !empty($this->_config['bindingOptions']['rule']) ? $this->_config['bindingOptions']['rule'] : null;
			$rule2 = !empty($this->_config['bindingOptions']['rule2']) ? $this->_config['bindingOptions']['rule2'] : null;
			$modelReference = $bindingModel->getReference(get_class($model), $rule1);
			$filterModelReference = $bindingModel->getReference($this->_config['model'], $rule2);

			$joinConditions = array();
			foreach ($modelReference['refColumns'] as $i => $refColumn) {
				$column = $modelReference['columns'][$i];
				$joinCondition = '';
				$joinCondition .= $bindingModel->getAdapter()->quoteIdentifier($refColumn);
				$joinCondition .= ' = ';
				$joinCondition .= $bindingModel->getAdapter()->quoteIdentifier($column);
				$joinConditions[] = $joinCondition;
			}
			$joinConditions = implode(' AND ', $joinConditions);

			$countSelect = $model->select()
				->from($model->getName(), array('c' => 'COUNT(*)'))
				->join($bindingModel->getName(), $joinConditions, array());
			if ($where = $browsebox->getOption('conditions')) {
				$countSelect->where($where);
			}

			foreach ($filterModelReference['columns'] as $i => $foreignKey) {
				$countSelect->where($bindingModel->getAdapter()->quoteIdentifier($foreignKey).' = ?', $this->_params[$i]);
			}
			$result = $model->fetchRow($countSelect);
			return $result->c;
		} else {
			throw new Garp_Browsebox_Filter_Exception_NotApplicable();
		}
	}
}
