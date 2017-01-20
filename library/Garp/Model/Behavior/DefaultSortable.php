<?php
/**
 * Garp_Model_Behavior_DefaultSortable
 * Makes sure a model is sorted by the same column by default for
 * every query, so developers are allowed to forget to add this
 * ORDER clause to their queries.
 *
 * @package Garp_Model_Behavior
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Model_Behavior_DefaultSortable extends Garp_Model_Behavior_Core {

    /**
     * Wether to execute before or after regular observers.
     *
     * @var string
     */
    protected $_executionPosition = self::EXECUTE_LAST;

    /**
     * Configuration.
     *
     * @param array $config
     * @return void
     */
    protected function _setup($config) {
    }

    /**
     * Before fetch callback.
     * Make sure a default order is set on queries.
     * This can be set thru $model::_defaultOrder. If not given,
     * $model->_primary will be used, but only when it is a single column.
     * Compound keys are ignored in this case.
     * All this is only applicable when no ORDER clause is available in the Select object.
     *
     * @param array $args
     * @return void
     */
    public function beforeFetch(&$args) {
        $model = &$args[0];
        $select = &$args[1];
        if ($select->getPart(Zend_Db_Select::ORDER)) {
            return;
        }
        $order = $model->getDefaultOrder() ?: $this->_getPrimaryKeyColumn($model);
        if (is_null($order)) {
            return;
        }
        $select->order(
            $this->_namespaceOrderColumn($order, $model, $select)
        );
    }

    /**
     * Namespace the sort column with the used alias.
     * Note that it only namespaces when the tableName is equal to the model's name.
     * In other words: if you've aliased the table in your query you are responsible for namespacing
     * your order clause.
     *
     * @param string $order
     * @param Garp_Model_Db $model
     * @param Zend_Db_Select $select
     * @return string
     */
    protected function _namespaceOrderColumn($order, Garp_Model_Db $model, Zend_Db_Select $select) {
        if (!is_array($order)) {
            $order = array($order);
        }
        $tableName = $model->getName();
        $from = $this->_getFrom($select);
        $order = array_map(
            function ($orderBit) use ($from) {
                $fromDefinition = array_filter(
                    $from,
                    function ($from) use ($orderBit) {
                        $orderColumnTest = preg_replace('/(ASC|DESC)$/', '', $orderBit);
                        $orderColumnTest = trim($orderColumnTest);
                        return in_array($orderColumnTest, $from['columns']);
                    }
                );
                if (!count($fromDefinition)) {
                    return $orderBit;
                }
                return array_get(current($fromDefinition), 'alias') . '.' . $orderBit;
            },
            $order
        );
        return $order;
    }

    protected function _getPrimaryKeyColumn(Garp_Model_Db $model) {
        $primary = $model->info(Zend_Db_Table_Abstract::PRIMARY);
        if (is_array($primary)) {
            return null;
        }
        return $primary . ' DESC';
    }

    protected function _getFrom(Zend_Db_Select $select) {
        $from = $select->getPart(Zend_Db_Select::FROM);
        $aliases = array_keys($from);
        foreach ($aliases as $i => $alias) {
            $from[$alias]['alias'] = $alias;
        }
        return $this->_addColumnsToFrom($from);
    }

    /**
     * Enrich FROM part with columns that belong to the table
     *
     * @param array $from
     * @return array
     */
    protected function _addColumnsToFrom($from) {
        return array_map(
            function ($fromPart) use ($from) {
                /**
                 * Oh dear, this is so fucking arbitrary... Passing the "id" as primary key to the
                 * anonymous Zend_Db_Table a couple lines below will blow up in case of habtm
                 * bindingModel. So we just... skip that if the name starts with an underscore?
                 * This'll keep me up at night. We should nuke this entire shitty component.
                 */
                if ($fromPart['tableName'][0] === '_') {
                    return array_set(
                        'columns',
                        array(),
                        $fromPart
                    );
                }
                $tableCls = new Zend_Db_Table(
                    array(
                        Zend_Db_Table::NAME => $fromPart['tableName'],
                        Zend_Db_Table::PRIMARY => 'id'
                    )
                );
                return array_set(
                    'columns',
                    $tableCls->info(Zend_Db_Table::COLS),
                    $fromPart
                );
            },
            $from
        );
    }

}



