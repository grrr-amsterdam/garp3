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
     * @param  callable $transform
     * @return Garp_Db_Table_Rowset
     */
    public function map(callable $transform): Garp_Db_Table_Rowset {
        $mapped = $this->reduce(
            function (array $acc, Zend_Db_Table_Row_Abstract $row) use ($transform): array {
                $transformed = $transform($row);
                if ($transformed instanceof Zend_Db_Table_Row_Abstract) {
                    $transformedArray = iterator_to_array($transformed);
                    $acc[] = array_merge(
                        $transformed->toArray(),
                        $transformed->getVirtual(),
                        $transformed->getRelated()
                    );
                } elseif (is_array($transformed)) {
                    $acc[] = $transformed;
                } else {
                    throw new LogicException(
                        'The callback given to Garp_Db_Table_Rowset::map must return either an array or an instance of Zend_Db_Table_Row_Abstract.'
                    );
                }
                return $acc;
            },
            []
        );

        return new static([
            'table'    => $this->getTable(),
            'data'     => $mapped,
            'readOnly' => $this->_readOnly,
            'rowClass' => $this->_rowClass,
            'stored'   => $this->_stored
        ]);
    }

    /**
     * Filter a rowset using $fn
     *
     * @param  callable $predicate
     * @return Garp_Db_Table_Rowset
     */
    public function filter(callable $predicate): Garp_Db_Table_Rowset {
        $filtered = $this->reduce(
            function (array $acc, Zend_Db_Table_Row_Abstract $row) use ($predicate): array {
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
            // Note: we clone the row to ensure the data in the rowset is immutable.
            // When iterating, Zend_Db_Table_Rowset_Abstract stores the row object internally for
            // the next iteration. This means anything done to the row in the callback will be
            // reflected in the original rowset. You would not be able to map, filter or reduce to
            // a new rowset without mutating the original data.
            $result = $fn($result, clone $row);
        }
        return $result;
    }

}
