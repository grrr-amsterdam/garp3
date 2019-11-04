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
        return new static([
            'table' => $this->_table,
            'rowClass' => $this->_rowClass,
            'data' => array_merge($this->_data, $that->getData()),
            'readOnly' => $this->_readOnly,
            'stored' => $this->_stored
        ]);
    }

    public function push(Garp_Db_Table_Row $row): Garp_Db_Table_Rowset {
        if (!$row instanceof $this->_rowClass) {
            throw new LogicException(
                sprintf('Unable to push row of type %s to this rowset. Expected: %s', get_class($row), $this->_rowClass)
            );
        }
        return $this->concat(new static([
            'table' => $this->_table,
            'rowClass' => $this->_rowClass,
            'data' => [$row->toArray()],
            'readOnly' => $this->_readOnly,
            'stored' => $this->_stored
        ]));
    }

    public function prepend(Garp_Db_Table_Row $row): Garp_Db_Table_Rowset {
        if (!$row instanceof $this->_rowClass) {
            throw new LogicException(
                sprintf('Unable to prepend row of type %s to this rowset. Expected: %s', get_class($row), $this->_rowClass)
            );
        }
        return (new static([
            'table' => $this->_table,
            'rowClass' => $this->_rowClass,
            'data' => [$row->toArray()],
            'readOnly' => $this->_readOnly,
            'stored' => $this->_stored
        ]))->concat($this);
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
        $out = new static([
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
     * @param  callable $predicate
     * @return Garp_Db_Table_Rowset
     */
    public function filter(callable $predicate): Garp_Db_Table_Rowset {
        $filtered = $this->reduce(
            function (array $acc, Zend_Db_Table_Row $row) use ($predicate): array {
                if ($predicate($row)) {
                    $acc[] = $row->toArray();
                }
                return $acc;
            },
            []
        );
        return new static([
            'table'    => $this->getTable(),
            'data'     => $filtered,
            'readOnly' => $this->_readOnly,
            'rowClass' => $this->_rowClass,
            'stored'   => $this->_stored
        ]);
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
