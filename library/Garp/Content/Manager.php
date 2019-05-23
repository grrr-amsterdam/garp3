<?php
/**
 * Garp_Content_Manager
 * Handles various crud methods
 *
 * @package Garp_Content
 * @author Harmen Janssen <harmen@grrr.nl>
 * @author David Spreekmeester <david@grrr.nl>
 * @author Ramiro Hammen <ramiro@grr.nl>
 */
class Garp_Content_Manager {
    /**
     * The currently manipulated model
     *
     * @var Garp_Model
     */
    protected $_model;

    /**
     * Note: with the coming of the REST API and transitioning into new Garp CMS territory, we want
     * to eventually shed the MySQL joint views (and any views for that matter).
     *
     * The `useJointView()` method is the first step in phasing it out. The REST api will make sure
     * it's set to FALSE, and when the old CMS is deprecated, we can remove it altogether.
     *
     * @var bool
     */
    protected $_usesJointView = true;

    /**
     * Class constructor
     *
     * @param Garp_Model|string $model The model to execute methods on
     * @return void
     */
    public function __construct($model) {
        if (is_string($model)) {
            $model = !$this->_modelNameIsPrefixed($model) ?
                Garp_Content_Api::modelAliasToClass($model) :
                $model;
            $model = new $model();
        }
        $model->setCmsContext(true);
        $this->_model = $model;
        if (!$this->_model instanceof Garp_Model) {
            throw new Garp_Content_Exception('The selected model must be a Garp_Model.');
        }
    }

    /**
     * Return the model
     *
     * @return Garp_Model
     */
    public function getModel() {
        return $this->_model;
    }

    /**
     * Fetch results from a model
     *
     * @param array $options Various fetching options (e.g. limit, sorting, etc.)
     * @return array
     */
    public function fetch(array $options = null) {
        try {
            $this->_checkAcl('fetch');
        } catch (Garp_Auth_Exception $e) {
            $this->_checkAcl('fetch_own');
        }

        if ($this->_model instanceof Garp_Model_Db) {
            $options = $options instanceof Garp_Util_Configuration
                ? $options
                : new Garp_Util_Configuration($options);
            $options
                ->setDefault('sort', array())
                ->setDefault('start', null)
                ->setDefault('limit', null)
                ->setDefault('fields', null)
                ->setDefault('query', false)
                ->setDefault('group', array())
                ->setDefault('rule', null)
                ->setDefault('bindingModel', null)
                ->setDefault('bidirectional', false)
                ->setDefault('filterForeignKeys', false);
            $options['sort']   = (array)$options['sort'];
            $options['fields'] = (array)$options['fields'];
            $tableName         = $this->_getTableName($this->_model);
            $options           = (array)$options;
            $modelInfo         = $this->_model->info();
            $referenceMap      = $modelInfo['referenceMap'];
            // SELECT
            // ============================================================
            $select = $this->_model->select();
            $select->setIntegrityCheck(false);

            // FILTER WHERES AND JOINS
            // ============================================================
            $related = array();
            if ($options['query'] && !empty($options['query'])) {
                /**
                 * Check for other model names in the conditions.
                 * These are indicated by a dot (".") in the name.
                 * If available, add these models as joins to the Select object.
                 * The format is <related-model-name>.<primary-key> => <value>.
                 */
                foreach ($options['query'] as $column => $value) {
                    if (strpos($column, '.') !== false) {
                        $related[$column] = $value;
                        unset($options['query'][$column]);
                    }
                }
            }

            // FROM
            // ============================================================
            if ($options['fields']) {
                $fields = $options['fields'];
            } elseif (count($related)) {
                // When using a join filter (used for the relationpanel), it's more performant to
                // specify only a model's list fields, otherwise the query can get pretty heavy for
                // tables with 100.000+ records.
                $primary = array_values($this->_model->info(Zend_Db_Table_Abstract::PRIMARY));
                $fields = array_merge($this->_model->getListFields(), $primary);
            } else {
                $fields = Zend_Db_Table_Select::SQL_WILDCARD;
            }
            // If filterForeignKeys is true, filter out the foreign keys
            if ($options['filterForeignKeys']) {
                $fields = $this->_filterForeignKeyColumns($fields, $referenceMap);
            }
            $select->from($tableName, $fields);

            // JOIN
            // ============================================================
            if (count($related)) {
                $this->_addJoinClause(
                    $select,
                    $related,
                    $options['rule'],
                    $options['bindingModel'],
                    $options['bidirectional']
                );
            }

            if (array_get($options, 'joinMultilingualModel')) {
                /**
                 * Note that this join is meant just to provide a table with its extended columns to
                 * allow, for instance, sorting.
                 * If you want to sort by `name`, which is often a multilingual field,
                 * you're gonna need this. MySQL would otherwise complain about unknown columns.
                 * Note that this replicates the behavior of the MySQL joint views.
                 */
                $i18nModel = $this->_model->getObserver('Translatable')
                    ->getI18nModel($this->_model);
                $lang = Garp_I18n::getDefaultLocale();
                $select->join(
                    $i18nModel->getName(),
                    $i18nModel->refmapToOnClause(get_class($this->_model)) . " AND lang = '$lang'",
                    array_map(
                        array_get('name'),
                        array_filter(
                            $this->_model->getFieldConfiguration(), array_get('multilingual')
                        )
                    )
                );
            }

            // WHERE
            // Add WHERE clause if there still remains something after
            // filtering.
            // ============================================================
            if ($options['query']) {
                $select->where($this->_createWhereClause($options['query']));
            }

            // GROUP
            // ============================================================
            $select->group($options['group']);

            // ORDER
            // ============================================================
            // Prefix native columns with the table name (e.g. "id" becomes
            // "Thing.id")
            // Note that we create a mock table object based on the joint view
            // to collect column info.
            // This should be more accurate than reading that info from the table.
            $mockTable = new Zend_Db_Table(
                array(
                Zend_Db_Table_Abstract::NAME => $tableName,
                Zend_Db_Table_Abstract::PRIMARY => $this->_model->info(
                    Zend_Db_Table_Abstract::PRIMARY
                ))
            );
            $nativeColumns = $mockTable->info(Zend_Db_Table_Abstract::COLS);

            $select->order(
                array_map(
                    function ($s) use ($tableName, $nativeColumns) {
                        $nativeColTest = preg_replace('/(ASC|DESC)$/', '', $s);
                        $nativeColTest = trim($nativeColTest);

                        if (in_array($nativeColTest, $nativeColumns) && strpos($s, '.') === false) {
                            $s = $tableName . '.' . $s;
                        }
                        return $s;
                    }, $options['sort']
                )
            );

            // LIMIT
            // ============================================================
            // Do not limit when a COUNT(*) is performed, this skews results.
            $isCountQuery = count($fields) == 1
                && !empty($fields[0])
                && strtolower($fields[0]) == 'count(*)';
            if (!$isCountQuery) {
                $select->limit($options['limit'], $options['start']);
            }
            $results = $this->_model->fetchAll($select);
        } else {
            $results = $this->_model->fetchAll();
        }

        foreach ($results as $result) {
            foreach ($result as $column => $value) {
                if (strpos($column, '.') !== false) {
                    $keyParts = explode('.', $column, 2);
                    $newKey = $keyParts[1];
                    $relModelKey = Garp_Util_String::strReplaceOnce(
                        $this->_model->getNameWithoutNamespace(), '', $keyParts[0]
                    );
                    $result['relationMetadata'][$relModelKey][$newKey] = $value;
                    unset($result[$column]);
                }
            }
        }
        return is_array($results) ? $results : $results->toArray();
    }


    /**
     * Count records according to given criteria
     *
     * @param Array $options Options
     * @return Int
     */
    public function count(array $options = null) {
        if ($this->_model instanceof Garp_Model_Db) {
            unset($options['sort']);
            $options['fields'] = 'COUNT(*)';
            try {
                $result = $this->fetch($options);
                return !empty($result[0]['COUNT(*)']) ? $result[0]['COUNT(*)'] : 0;
            } catch (Zend_Db_Statement_Exception $e) {
                /**
                 * @todo When fetching results
                 * filtered using a HABTM relationship, extra
                 * meta field are returned from the binding table.
                 * This results in an SQL error; when using COUNT()
                 * and returning results from multiple tables a
                 * GROUP BY clause is mandatory. This must be fixed in
                 * the future.
                 */
                return 1000;
            }
        } else {
            return $this->_model->count();
        }
    }

    /**
     * Create new record
     *
     * @param array $data The new record's data as key => value pairs.
     * @return mixed The primary key of the new record
     */
    public function create(array $data) {
        $this->_checkAcl('create');
        $pk = $this->_model->insert($data);
        return $pk;
    }

    /**
     * Update existing record
     *
     * @param array $data The record's new data
     * @return int The amount of updated rows
     */
    public function update(array $data) {
        // check if primary key is available
        $prim = $this->_model->info(Zend_Db_Table::PRIMARY);
        if (!is_array($prim)) {
            $prim = array($prim);
        }

        $where = array();
        foreach ($prim as $key) {
            if (!array_key_exists($key, $data)) {
                throw new Garp_Content_Exception('Primary key ' . $key . ' not available in data');
            }
            $where[] = $this->_model->getAdapter()->quoteInto($key . ' = ?', $data[$key]);
            unset($data[$key]);
        }
        $where = implode(' AND ', $where);

        try {
            /**
             * First, see if the user is allowed to update everything
             */
            $this->_checkAcl('update');
        } catch (Garp_Auth_Exception $e) {
            /**
             * If that fails, check if the user is allowed to update her own material
             * AND if the current item is hers.
             */
            $this->_checkAcl('update_own');

            /**
             * Good, the user is allowed to 'update_own'. In that case we have to check if
             * the current item is actually the user's.
             */
            if (!$this->_itemBelongsToUser($data, $where)) {
                throw new Garp_Auth_Exception('You are only allowed to edit your own material.');
            }
        }
        return $this->_model->update($data, $where);
    }

    /**
     * Delete (a) record(s)
     *
     * @param array $where WHERE clause, specifying which records to delete
     * @return bool
     */
    public function destroy(array $where) {
        $where = $this->_createWhereClause($where, 'AND', false);
        try {
            /**
             * First, see if the user is allowed to update everything
             */
            $this->_checkAcl('destroy');
            $this->_model->delete($where);
        } catch (Garp_Auth_Exception $e) {
            /**
             * If that fails, check if the user is allowed to update her own material
             * AND if the current item is hers.
             */
            $this->_checkAcl('destroy_own');

            /**
             * Good, the user is allowed to 'destroy_own'. In that case we have to check
             * if the current item is actually the user's.
             */
            $rows = $this->_model->fetchAll($where);
            foreach ($rows as $row) {
                if (!$this->_itemBelongsToUser($row->toArray())) {
                    throw new Garp_Auth_Exception(
                        'You are only allowed to delete your own material.'
                    );
                }
                $row->delete();
            }
        }
    }

    /**
     * Relate entities to each other, optionally removing previous existing relations.
     *
     * @param array $options
     * @return bool
     */
    public function relate(array $options) {
        $this->_checkAcl('relate');

        extract($options);
        if (!isset($primaryKey) || !isset($model) || !isset($foreignKeys)) {
            throw new Garp_Content_Exception(
                'Not enough options. "primaryKey", "model" and "foreignKeys" are required.'
            );
        }
        $model         = Garp_Content_Api::modelAliasToClass($model);
        $primaryKey    = (array)$primaryKey;
        $foreignKeys   = (array)$foreignKeys;
        $rule          = isset($rule) ? $rule : null;
        $rule2         = isset($rule2) ? $rule2 : null;
        $bindingModel  = isset($bindingModel) ? 'Model_' . $bindingModel : null;
        $bidirectional = isset($bidirectional) ? $bidirectional : null;

        if (isset($bindingModel)) {
            $bindingModel = new $bindingModel();
            $bindingModel->setCmsContext(true);
        }

        if (array_key_exists('unrelateExisting', $options) && $options['unrelateExisting']) {
            Garp_Content_Relation_Manager::unrelate(
                array(
                    'modelA'        => $this->_model,
                    'modelB'        => $model,
                    'keyA'          => $primaryKey,
                    'rule'          => $rule,
                    'ruleB'         => $rule2,
                    'bindingModel'  => $bindingModel,
                    'bidirectional' => $bidirectional,
                )
            );
        }

        $success = $attempts = 0;

        foreach ($foreignKeys as $i => $relationData) {
            if (!array_key_exists('key', $relationData)) {
                throw new Garp_Content_Exception('Foreign key is a required key.');
            }

            $foreignKey  = $relationData['key'];
            $extraFields = array_key_exists('relationMetadata', $relationData)
                ? $relationData['relationMetadata']
                : array();

            if (Garp_Content_Relation_Manager::relate(
                array(
                'modelA'        => $this->_model,
                'modelB'        => $model,
                'keyA'          => $primaryKey,
                'keyB'          => $foreignKey,
                'extraFields'   => $extraFields,
                'rule'          => $rule,
                'ruleB'         => $rule2,
                'bindingModel'  => $bindingModel,
                'bidirectional' => $bidirectional,
                )
            )
            ) {
                $success++;
            }
            $attempts++;
        }
        return $success == $attempts;
    }

    /**
     * Unrelate entities from each other
     *
     * @param array $options
     * @return bool
     */
    public function unrelate(array $options) {
        $this->_checkAcl('relate');

        extract($options);
        if (!isset($primaryKey) || !isset($model) || !isset($foreignKeys)) {
            throw new Garp_Content_Exception(
                'Not enough options. "primaryKey", "model" and "foreignKeys" are required.'
            );
        }
        $model         = Garp_Content_Api::modelAliasToClass($model);
        $primaryKey    = (array)$primaryKey;
        $foreignKeys   = (array)$foreignKeys;
        $rule          = isset($rule) ? $rule : null;
        $rule2         = isset($rule2) ? $rule2 : null;
        $bindingModel  = isset($bindingModel) ? 'Model_' . $bindingModel : null;
        $bidirectional = isset($bidirectional) ? $bidirectional : null;

        Garp_Content_Relation_Manager::unrelate(
            array(
                'modelA'        => $this->_model,
                'modelB'        => $model,
                'keyA'          => $primaryKey,
                'keyB'          => $foreignKeys,
                'rule'          => $rule,
                'ruleB'         => $rule2,
                'bindingModel'  => $bindingModel,
                'bidirectional' => $bidirectional,
            )
        );
    }

    /**
     * Check wether the joint view should be used.
     *
     * @return bool
     */
    public function usesJointView() {
        return $this->_usesJointView;
    }

    /**
     * Restrict usage of joint view altogether
     *
     * @param bool $yepnope Wether to use it
     * @return Garp_Content_Manager
     */
    public function useJointView($yepnope) {
        $this->_usesJointView = $yepnope;
        return $this;
    }

    /**
     * Create a WHERE clause for use with Zend_Db_Select
     *
     * @param array $query WHERE options
     * @param string $separator AND/OR
     * @param bool $useJointView Wether to use the *_joint view or the table.
     * @return string WHERE clause
     */
    protected function _createWhereClause(array $query, $separator = 'AND', $useJointView = true) {
        $where = array();
        $adapter = $this->_model->getAdapter();
        $nativeColumns = $this->_model->info(Zend_Db_Table_Abstract::COLS);
        if ($useJointView) {
            $tableName = $this->_getTableName($this->_model);
            $mockTable = new Zend_Db_Table(
                array(
                Zend_Db_Table_Abstract::NAME => $tableName,
                Zend_Db_Table_Abstract::PRIMARY => $this->_model->info(
                    Zend_Db_Table_Abstract::PRIMARY
                ))
            );
            $nativeColumns = $mockTable->info(Zend_Db_Table_Abstract::COLS);
        } else {
            $tableName = $this->_model->getName();
        }

        // change native columns to lowercase
        // because when columnName is configured in camelcase in the model config
        // it causes problems when checking if refColumn is a native column
        $nativeColumns = array_map(
            function ($column) {
                return strtolower($column);
            }, $nativeColumns
        );

        foreach ($query as $column => $value) {
            if (strtoupper($column) === 'OR' && is_array($value)) {
                $where[] = $this->_createWhereClause($value, 'OR');
            } elseif (is_array($value)) {
                $where[] = $adapter->quoteInto(
                    $adapter->quoteIdentifier($tableName) . '.' . $column . ' IN(?)',
                    $value
                );
            } elseif (is_null($value)) {
                if (substr($column, -2) == '<>') {
                    $column = preg_replace('/<>$/', '', $column);
                    $where[] = $column . ' IS NOT NULL';
                } else {
                    $where[] = $column . ' IS NULL';
                }
            } elseif (is_scalar($value)) {
                // Use $refColumn to see if this column is native to the current
                // model.
                $refColumn = null;
                if (!preg_match('/(>=?|<=?|like|<>)/i', $column, $matches)) {
                    $refColumn = $column;
                    $column = $adapter->quoteIdentifier($column) . ' =';
                } else {
                    // explode column so the actual column name can be quoted
                    $parts = explode(' ', $column);
                    $refColumn = $parts[0];
                    $column = $adapter->quoteIdentifier($parts[0]) . ' ' . $parts[1];
                }

                if (strpos($refColumn, '.') === false && in_array($refColumn, $nativeColumns)) {
                    $column = $adapter->quoteIdentifier($tableName) . '.' . $column;
                }
                $where[] = $adapter->quoteInto($column . ' ?', $value);
            }
        }
        return '(' . implode(" $separator ", $where) . ')';
    }

    /**
     * Add a JOIN clause to a Zend_Db_Select object
     *
     * @param Zend_Db_Select $select The select object
     * @param array $related Collection of related models
     * @param string $rule Used to figure out the relationship metadata from the referencemap
     * @param string $bindingModel Binding model used in HABTM relations
     * @param bool $bidirectional
     * @return void
     */
    protected function _addJoinClause(Zend_Db_Select $select, array $related, $rule = null,
        $bindingModel = null, $bidirectional = true
    ) {
        foreach ($related as $filterModelName => $filterValue) {
            $fieldInfo = explode('.', $filterModelName, 2);
            $filterModelName = Garp_Content_Api::modelAliasToClass($fieldInfo[0]);

            $filterColumn = $fieldInfo[1];
            $filterModel = new $filterModelName();
            /**
             * Determine wether a negation clause (e.g. !=) is requested
             * and normalize the filterColumn.
             */
            $negation = strpos($filterColumn, '<>') !== false;
            $filterColumn = str_replace(' <>', '', $filterColumn);

            if ($filterModelName === get_class($this->_model)) {
                /*  This is a homophile relation and the current condition touches the
                    homophile model.
                    The following condition prevents a 'relatable' list to include the
                    current record, because a record cannot be related to itself.
                */
                $select->where(
                    $this->_getTableName($filterModel) . '.' . $filterColumn . ' != ?',
                    $filterValue
                );
            }

            try {
                // the other model is a child
                $reference = $filterModel->getReference(get_class($this->_model), $rule);
                $this->_addHasManyClause(
                    array(
                    'select'        => $select,
                    'filterModel'   => $filterModel,
                    'reference'     => $reference,
                    'filterColumn'  => $filterColumn,
                    'filterValue'   => $filterValue,
                    'negation'      => $negation
                    )
                );
            } catch (Zend_Db_Table_Exception $e) {
                try {
                    // the other model is the parent
                    $reference = $this->_model->getReference(get_class($filterModel), $rule);
                    $this->_addBelongsToClause(
                        array(
                        'select'        => $select,
                        'reference'     => $reference,
                        'filterColumn'  => $filterColumn,
                        'filterValue'   => $filterValue,
                        'negation'      => $negation
                        )
                    );
                } catch (Zend_Db_Table_Exception $e) {
                    try {
                        // the models are equal; a binding model is needed
                        $this->_addHasAndBelongsToManyClause(
                            array(
                            'select'        => $select,
                            'filterModel'   => $filterModel,
                            'filterColumn'  => $filterColumn,
                            'filterValue'   => $filterValue,
                            'negation'      => $negation,
                            'bindingModel'  => $bindingModel,
                            'bidirectional' => $bidirectional
                            )
                        );
                    } catch (Zend_Db_Table_Exception $e) {
                        throw $e;
                    }
                }
            }
        }
    }

    /**
     * Add a hasMany/hasOne filter to a Zend_Db_Select object.
     * Example query:
     * SELECT * FROM users
     * INNER JOIN comments ON comments.user_id = users.id
     * WHERE comments.id = 5
     *
     * @param Array $options Collection of options containing;
     * ['select']       Zend_Db_Select  The select object
     * ['filterModel']  Garp_Model_Db   The filtering model
     * ['filterColumn'] string          The column used as the query filter
     * ['filterValue']  mixed           The value used as the query filter
     * ['reference']    array           The relation as in the reference map of the model
     * ['negation']     bool            Wether the query should include or exclude
     *                                  matches found by $filterValue
     * @return void
     */
    protected function _addHasManyClause(array $options) {
        // keys of $options available in the local space as variables
        extract($options);
        $select->distinct();

        $filterModelName = $filterModel->getName();
        $thisTableName = $this->_getTableName($this->_model);
        // in the case of homophile relationships...
        if ($filterModelName == $thisTableName) {
            $filterModelName = $filterModelName . '_2';
        }

        foreach ($reference['refColumns'] as $i => $column) {
            if ($column === $filterColumn) {
                $joinColumn = $thisTableName . '.' . $column;
                /**
                 * Map the index of the found column to the foreign key column.
                 * Note that these columns are paired by index,
                 * so the order in the reference map must be the same.
                 */
                $foreignKeyColumn = $filterModelName . '.' . $reference['columns'][$i];
                break;
            }
        }
        if (!isset($joinColumn)) {
            throw new Garp_Content_Exception(
                'The relationship between ' . get_class($this->_model) . ' and ' .
                get_class($filterModel) . ' cannot be determined from the ' .
                'reference map.'
            );
        }

        $bindingCondition = $foreignKeyColumn . ' = ' . $joinColumn;
        $bindingCondition .= $filterModel->getAdapter()->quoteInto(
            ' AND ' . $filterModelName . '.' . $filterColumn . ' = ?',
            $filterValue
        );

        $select->joinLeft(
            array($filterModelName => $filterModelName),
            $bindingCondition,
            array()
        );
        /**
         * Cause MySQL developers are fucking cunts, ([NULL] != 35) === FALSE.
         * So in the case of a negation an extra WHERE clause is needed that
         * checks for NULL.
         */
        $nullFix = $negation ? " OR $foreignKeyColumn IS NULL" : '';
        $operator = $negation ? '!=' : '=';
        $select->where(
            "({$filterModelName}.$filterColumn $operator ?" . $nullFix . ")",
            $filterValue
        );
    }

    /**
     * Add a belongsto filter to a Zend_Db_Select object.
     * Example query:
     * SELECT * FROM comments
     * WHERE comments.user_id = 35
     *
     * @param array $options Collection of options containing;
     * ['select']       Zend_Db_Select  The select object
     * ['filterColumn'] string          The column used as the query filter
     * ['filterValue']  mixed           The value used as the query filter
     * ['reference']    array           The relation as in the reference map of the model
     * ['negation']     bool            Wether the query should include or exclude
     *                                  matches found by $filterValue
     * @return void
     */
    protected function _addBelongsToClause(array $options) {
        $thisTableName = $this->_getTableName($this->_model);
        // keys of $options available in the local space as variables
        extract($options);
        foreach ($reference['refColumns'] as $i => $column) {
            if ($column === $filterColumn) {
                /**
                 * Map the index of the found column to the foreign key column.
                 * Note that these columns are paired by index,
                 * so the order in the reference map must be the same.
                 */
                $filterColumn = $thisTableName . '.' . $reference['columns'][$i];
                break;
            }
        }
        /**
         * Cause MySQL developers are fucking cunts, ([NULL] != 35) === FALSE.
         * So in the case of a negation an extra WHERE clause is needed that
         * checks for NULL.
         */
        $nullFix = $negation ? " OR $filterColumn IS NULL" : '';
        $operator = $negation ? '!=' : '=';
        $select->where("($filterColumn $operator ?$nullFix)", $filterValue);
    }

    /**
     * Add a hasAndBelongsToMany filter to a Zend_Db_Select object.
     * Example query:
     * SELECT *
     * FROM tags
     * LEFT JOIN tags_users ON tags_users.tag_id = tags.id AND user_id = 35
     * INNER JOIN `users` ON users.id = tags_users.user_id
     * WHERE user_id 35 // in the case of negation, this'll be "WHERE user_id IS NULL"
     *
     * @param array $options Collection of options containing;
     * ['select']       Zend_Db_Select  The select object
     * ['filterModel']  Garp_Model_Db   The filtering model
     * ['filterColumn'] string          The column used as the query filter
     * ['filterValue']  mixed           The value used as the query filter
     * ['negation']     bool            Wether the query should include or exclude
     *                                  matches found by $filterValue
     * ['bidirectional'] bool           Wether homophile relationships should be queried
     *                                  bidirectionally
     * @return void
     */
    protected function _addHasAndBelongsToManyClause(array $options) {
        if (!isset($options['bindingModel'])) {
            $modelNames = array(
                $this->_model->getNameWithoutNamespace(),
                $options['filterModel']->getNameWithoutNamespace()
            );
            sort($modelNames);
            $bindingModelName = 'Model_' . implode('', $modelNames);
        } else {
            $bindingModelName = 'Model_' . $options['bindingModel'];
        }
        $bindingModel = new $bindingModelName();
        $thisTableName = $this->_getTableName($this->_model);
        $bindingModelTable = $bindingModel->getName();

        $reference = $bindingModel->getReference(get_class($this->_model));
        foreach ($reference['refColumns'] as $i => $column) {
            if ($column === $options['filterColumn']) {
                $bindingModelForeignKeyField = $reference['columns'][$i];
                $foreignKeyField = $column;
                break;
            }
        }

        $reference = $bindingModel->getReference(
            get_class($options['filterModel']),
            $this->_findSecondRuleKeyForHomophiles($options['filterModel'], $bindingModel)
        );
        foreach ($reference['refColumns'] as $i => $column) {
            if ($column === $options['filterColumn']) {
                $filterField = $reference['columns'][$i];
                break;
            }
        }

        $bindingCondition = $bindingModelTable . '.' . $bindingModelForeignKeyField . ' = ' .
            $thisTableName . '.' . $foreignKeyField;
        $bindingCondition .= $bindingModel->getAdapter()->quoteInto(
            ' AND ' . $bindingModelTable . '.' . $filterField . ' = ?',
            $options['filterValue']
        );
        if ($this->_isHomophile($options['filterModel']) && array_get($options, 'bidirectional')) {
            $bindingCondition .= ' OR ' . $bindingModelTable . '.'
                . $filterField . ' = ' . $thisTableName . '.' . $foreignKeyField;
            $bindingCondition .= $bindingModel->getAdapter()->quoteInto(
                ' AND ' . $bindingModelTable . '.' . $bindingModelForeignKeyField . ' = ?',
                $options['filterValue']
            );
        }

        // Add columns of bindingTable to the query (namespaced using dot)
        $tmpBindingColumns = $bindingModel->info(Zend_Db_Table::COLS);
        $bindingColumns = array();
        $weighableColumns = array();
        if ($weighable = $bindingModel->getObserver('Weighable')) {
            $weighableColumns = $weighable->getWeightColumns();
        }
        foreach ($tmpBindingColumns as $bc) {
            // Exclude foreign key fields
            if (in_array($bc, array($bindingModelForeignKeyField, $filterField))) {
                continue;
            }
            // Exclude columns generated by a Weighable behavior
            if (in_array($bc, $weighableColumns)) {
                continue;
            }
            $bindingColumns[$bindingModel->getNameWithoutNamespace() . '.' . $bc] = $bc;
        }

        $options['select']->joinLeft($bindingModelTable, $bindingCondition, $bindingColumns);

        if ($options['negation']) {
            $options['select']->where($bindingModelTable . '.' . $filterField . ' IS NULL');
        } else {
            $options['select']->where(
                $bindingModelTable . '.' . $filterField . ' = ?',
                $options['filterValue']
            );
            if ($this->_isHomophile($options['filterModel'])
                && array_get($options, 'bidirectional')
            ) {
                $options['select']->orWhere(
                    $bindingModelTable . '.' . $bindingModelForeignKeyField . ' = ?',
                    $options['filterValue']
                );
                $options['select']->group('id');
            }
        }

        // Allow behaviors to modify the SELECT object
        $bindingModel->notifyObservers('beforeFetch', array($bindingModel, $options['select']));
    }

    /**
     * Filter columns that are foreign keys.
     *
     * @param array $fields All columns
     * @param array $referenceMap The model's referenceMap
     * @return array
     */
    protected function _filterForeignKeyColumns($fields, $referenceMap) {
        $out = array();
        $foreignKeys = array();
        // create an array of foreign keys...
        foreach ($referenceMap as $relName => $relConfig) {
            $foreignKeys = array_merge($foreignKeys, (array)$relConfig['columns']);
        }
        // ...and return the values that are not in that array.
        return array_diff($fields, $foreignKeys);
    }

    /**
     * Checks if this is a homophile relation: an association between records of the same model.
     *
     * @param Garp_Model_Db $filterModel
     * @return bool
     */
    protected function _isHomophile(Garp_Model_Db $filterModel) {
        return get_class($this->_model) === get_class($filterModel);
    }

    /**
     * In case of a homophile relation, this function returns the key to the second rule,
     * to prevent the same rule being returned twice, because both rules in
     * a homophile binding model point to the same related model.
     *
     * @param Garp_Model_Db $filterModel
     * @param string $bindingModel
     * @return string|null Returns the name of the second rule key in the reference map,
     * if this is a homophile relation. Otherwise, null is returned.
     */
    protected function _findSecondRuleKeyForHomophiles($filterModel, $bindingModel) {
        $homophileSecondRuleKey = null;

        if ($this->_isHomophile($filterModel)) {
            $bindingReferenceMap = $bindingModel->info(Zend_Db_Table_Abstract::REFERENCE_MAP);

            $foundRelevantRule = false;
            foreach ($bindingReferenceMap as $ruleKey => $rule) {
                if ($rule['refTableClass'] === get_class($this->_model)) {
                    if ($foundRelevantRule) {
                        $homophileSecondRuleKey = $ruleKey;
                    }
                    $foundRelevantRule = !$foundRelevantRule;
                }
            }
        }

        return $homophileSecondRuleKey;
    }

    /**
     * Check to see if the current model supports the requested method
     *
     * @param string $method The method
     * @return bool
     * @throws Garp_Content_Exception If the method is not supported
     */
    protected function _checkAcl($method) {
        if (!Garp_Auth::getInstance()->isAllowed(get_class($this->_model), $method)) {
            throw new Garp_Auth_Exception('You are not allowed to execute the requested action.');
        }
    }

    /**
     * Check a record belongs to the currently logged in user.
     * This check is based on the author_id column.
     *
     * @param array $data The record data. Primary key must be present here.
     * @param string $where A WHERE clause to find the record
     * @return bool
     */
    protected function _itemBelongsToUser($data, $where = false) {
        $userData = Garp_Auth::getInstance()->getUserData();
        $userId = $userData['id'];
        if (!array_key_exists('author_id', $data)) {
            if (!$where) {
                return false;
            }
            // fetch the record based on the given WHERE clause
            $row = $this->_model->fetchRow($where);
            if (!$row || !$row->author_id) {
                return false;
            }
            $data = $row->toArray();
        }
        return $userId == $data['author_id'];
    }

    /**
     * Check wether a full modelname is given.
     * TODO It might be a good idea to extend this with other prefixes?
     * TODO Or actually just remove the whole aliasing bullshit if we
     * TODO have to do arbitrary checks like this...
     *
     * @param string $modelName
     * @return bool
     */
    protected function _modelNameIsPrefixed($modelName) {
        return strpos($modelName, 'Model_') === 0 || strpos($modelName, 'Garp_Model_Db_') === 0;
    }

    /**
     * Negotiate between joint view and actual table name.
     *
     * @param Garp_Model_Db $model
     * @return string
     */
    protected function _getTableName($model) {
        if (!$this->usesJointView()) {
            return $model->getName();
        }
        return $model->getJointView() ?: $model->getName();
    }
}

