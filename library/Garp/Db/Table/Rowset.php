<?php
use Garp\Functional\Types\TypeClasses\Semigroup;

/**
 * @package Garp3
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Db_Table_Rowset extends Zend_Db_Table_Rowset_Abstract implements Semigroup {

    public function getData(): array {
        return $this->_data;
    }

    /**
     * Concatenate two semigroups.
     *
     * @param  Semigroup $that
     * @return Semigroup
     */
    public function concat(Semigroup $that): Semigroup {
        if (!$that instanceof self) {
            throw new LogicException(
                sprintf('Unable to concatenate semigroups %s and %s', get_class($this), get_class($that))
            );
        }
        return new self([
            'table' => $this->_table,
            'rowClass' => $this->_rowClass,
            'data' => array_merge($this->_data, $that->getData()),
            'readOnly' => $this->_readOnly,
            'stored' => $this->_stored
        ]);
    }

    /**
     * Flatten a rowset to a simpler array containing the specified columns.
     *
     * @param  mixed $column Array of columns or column.
     * @return array
     */
    public function flatten($column) {
        $out = array();
        foreach ($this as $row) {
            $out[] = $row->flatten($column);
        }
        return $out;
    }

    /**
     * Project a rowset using $fn
     *
     * @param  callable $fn
     * @return Garp_Db_Table_Rowset
     */
    public function map(callable $fn) {
        $rows = array_map($fn, $this->toArray());
        $out = new self([
            'table'    => $this->getTable(),
            'data'     => $rows,
            'readOnly' => $this->_readOnly,
            'rowClass' => $this->_rowClass,
            'stored'   => $this->_stored
        ]);
        return $out;
    }

    /**
     * Filter a rowset using $fn
     *
     * @param  callable $fn
     * @return Garp_Db_Table_Rowset
     */
    public function filter($fn): Garp_Db_Table_Rowset {
        $rows = array_values(array_filter($this->toArray(), $fn));
        $out = new self([
            'table'    => $this->getTable(),
            'data'     => $rows,
            'readOnly' => $this->_readOnly,
            'rowClass' => $this->_rowClass,
            'stored'   => $this->_stored
        ]);
        return $out;
    }

    /**
     * Reduce a rowset by $fn from $initial
     *
     * @param callable $fn
     * @param mixed $initial
     * @return mixed
     */
    public function reduce(callable $fn, $initial) {
        $result = $initial;
        foreach ($this as $row) {
            $result = $fn($result, $row);
        }
        return $result;
    }

}
