<?php
/**
 * Garp_Content_Api_Rest
 * RESTful content API
 *
 * @package Garp_Content_Api
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Content_Api_Rest {

    const EXCEPTION_MISSING_MODEL = 'Missing required parameter \'model\'';
    const EXCEPTION_INVALID_JSON = 'Parameter \'options\' contains invalid json: %s';
    const EXCEPTION_MISSING_POSTDATA = 'Empty payload';
    const EXCEPTION_POST_WITH_ID = 'Do not use method POST to update existing records. Use PUT.';
    const EXCEPTION_PUT_WITHOUT_ID = 'Do not use method PUT to create a new record. Use POST.';
    const EXCEPTION_MISSING_ID = 'Missing required id';
    const EXCEPTION_MISSING_RELATED_ID = 'Missing required related id';
    const EXCEPTION_NO_DICTIONARY = 'There is no dictionary for this app';

    const DEFAULT_PAGE_LIMIT = 20;

    const METHOD_DICTIONARY = 'dictionary';

    /**
     * GET results
     *
     * @param array $params
     * @return array
     */
    public function get(array $params) {
        $this->_requireDataType($params);

        if (array_get($params, 'datatype') === self::METHOD_DICTIONARY) {
            return $this->getDictionary();
        } elseif (array_get($params, 'relatedType') && array_get($params, 'id')) {
            list($response, $httpCode) = $this->_getRelatedResults($params);
        } elseif (array_get($params, 'id')) {
            list($response, $httpCode) = $this->_getSingleResult($params);
        } else {
            list($response, $httpCode) = $this->_getIndex($params);
        }

        return $this->_formatResponse($response, $httpCode);
    }

    /**
     * POST a new record
     *
     * @param array $params
     * @param array $postData
     * @return array
     */
    public function post(array $params, array $postData) {
        $this->_validatePostParams($params, $postData);
        $model = $this->_normalizeModelName($params['datatype']);
        $contentManager = $this->_getContentManager($model);
        $primaryKey = $contentManager->create($postData);
        $record = array_get(
            array_get(
                $this->get(
                    array(
                        'datatype' => $params['datatype'],
                        'id' => $primaryKey
                    )
                ),
                'data',
                array()
            ),
            'result',
            array()
        );
        $response = array(
            'success' => true,
            'result' => $record,
            'link' => (string)new Garp_Util_FullUrl(
                array(array('datatype' => $params['datatype'], 'id' => $primaryKey), 'rest')
            )
        );
        return $this->_formatResponse($response, 201);
    }

    /**
     * PUT changes into an existing record
     *
     * @param array $params
     * @param array $postData
     * @return array
     */
    public function put(array $params, array $postData) {
        $this->_requireDataType($params);
        if (!array_get($params, 'id')) {
            throw new Garp_Content_Api_Rest_Exception(
                self::EXCEPTION_PUT_WITHOUT_ID
            );
        }
        $model = $this->_normalizeModelName($params['datatype']);

        // First, see if the record actually exists
        list($record) = $this->_getSingleResult($params);
        if (is_null($record['result'])) {
            return $this->_formatResponse(array('success' => false), 404);
        }

        if (!array_get($params, 'relatedType')) {
            $this->_updateSingle($params, $postData);
            list($response, $httpCode) = $this->_getSingleResult($params);
        } else {
            $schema = new Garp_Content_Api_Rest_Schema('rest');
            // Sanity check if related model exists
            list($relatedRecord) = $this->_getSingleResult(
                array(
                    'datatype' => getProperty(
                        'model',
                        $schema->getRelation(
                            $params['datatype'],
                            $params['relatedType']
                        )
                    ),
                    'id' => $params['relatedId']
                )
            );
            if (!$relatedRecord['result']) {
                return $this->_formatResponse(array('success' => false), 404);
            }

            $this->_addRelation($params, $postData);
            list($response, $httpCode) = $this->_getRelatedResults($params);
        }
        return $this->_formatResponse($response, $httpCode);
    }

    /**
     * PATCH a record with a partial update
     *
     * @param array $params
     * @param array $postData
     * @return array
     */
    public function patch(array $params, array $postData) {
        // For now, keep it equivalent to PUT.
        // I might someday implement its formal definition.
        return $this->put($params, $postData);
    }

    /**
     * DELETE a record
     *
     * @param array $params
     * @return bool
     */
    public function delete(array $params) {
        $this->_requireDataType($params);
        if (!array_get($params, 'id')) {
            throw new Garp_Content_Api_Rest_Exception(
                self::EXCEPTION_MISSING_ID
            );
        }
        // First, see if the record actually exists
        list($record) = $this->_getSingleResult($params);
        if (is_null($record['result'])) {
            return $this->_formatResponse(array('success' => false), 404);
        }

        if (array_get($params, 'relatedType')) {
            $this->_removeRelation($params);
            return $this->_formatResponse(null, 204, false);
        }
        $contentManager = $this->_getContentManager($params['datatype']);
        $contentManager->destroy(array('id' => $params['id']));
        return $this->_formatResponse(null, 204, false);
    }

    /**
     * HEAD
     *
     * @param array $params
     * @return array
     */
    public function head(array $params) {
        $response = $this->get($params);
        // set "render" to false
        $response[2] = false;
        return $response;
    }

    /**
     * OPTIONS
     *
     * @param array $params
     * @return array
     */
    public function options(array $params) {
        $schema = new Garp_Content_Api_Rest_Schema('rest');
        if (!array_get($params, 'datatype')) {
            $out = array();
            $out['root'] = (string)new Garp_Util_FullUrl(array(array(), 'rest'));
            $out['i18n'] = array(
                'locales' => Garp_I18n::getLocales(),
                'default' => Garp_I18n::getDefaultLocale()
            );

            $out['urls'] = $this->_getUrlsForOptions();

            $out['models'] = $schema->getModelPaths();
            return $this->_formatResponse($out, 200);
        }
        if (array_get($params, 'id') || array_get($params, 'relatedType')) {
            return $this->_formatResponse(null, 200, false);
        }
        return $this->_formatResponse($schema->getModelDetails($params['datatype']), 200);
    }

    /**
     * Grab the dictionary containing all translatable strings.
     *
     * @return array
     */
    public function getDictionary() {
        if (!Zend_Registry::isRegistered('Zend_Translate')) {
            throw new Garp_Content_Api_Rest_Exception(
                self::EXCEPTION_NO_DICTIONARY
            );
        }
        $out = array(
            'results' => Zend_Registry::get('Zend_Translate')->getMessages(),
            'success' => true
        );
        return $this->_formatResponse($out, 200);
    }

    /**
     * Format a response object
     *
     * @param mixed $data The actual response from the API
     * @param int $httpCode The accompanying HTTP code
     * @param bool $render Wether a response should be rendered. (DELETE and HEAD requests might not
     *                                                            render)
     * @return array
     */
    protected function _formatResponse($data, $httpCode, $render = true) {
        return compact('data', 'httpCode', 'render');
    }

    /**
     * Remove cruft from HTTP params and provide sensible defaults.
     *
     * @param array $params The combined URL parameters
     * @return array
     */
    protected function _extractOptionsForFetch(array $params) {
        if (!isset($params['options'])) {
            $params['options'] = array();
        }
        try {
            $options = is_string($params['options']) ?
                Zend_Json::decode(urldecode($params['options'])) :
                $params['options'];
        } catch (Zend_Json_Exception $e) {
            throw new Garp_Content_Api_Rest_Exception(
                sprintf(self::EXCEPTION_INVALID_JSON, $e->getMessage())
            );
        }

        $options = array_get_subset(
            $options,
            array(
                'sort', 'start', 'limit', 'fields', 'query', 'group', 'with'
            )
        );
        if (!isset($options['limit'])) {
            $options['limit'] = self::DEFAULT_PAGE_LIMIT;
        }
        if (isset($options['with'])) {
            // Normalize into an array
            $options['with'] = (array)$options['with'];
        }
        return $options;
    }

    /**
     * Get index of items.
     * (the common GET operation without :id)
     *
     * @param array $params
     * @return array
     */
    protected function _getIndex(array $params) {
        xdebug_break();
        $model = $this->_normalizeModelName($params['datatype']);
        $contentManager = $this->_getContentManager($model);
        $options = $this->_extractOptionsForFetch($params);

        $records = $contentManager->fetch($options);
        if (isset($options['with'])) {
            $records = $this->_combineRecords($params['datatype'], $records, $options['with']);
        }
        return array(
            array(
                'success' => true,
                'result' => $records,
                'amount' => count($records),
                'total' => intval($contentManager->count($options))
            ),
            200
        );
    }

    protected function _getSingleResult(array $params) {
        $model = $this->_normalizeModelName($params['datatype']);
        $options = array(
            'query' => array(
                'id' => $params['id']
            )
        );
        $contentManager = $this->_getContentManager($model);
        $result = $contentManager->fetch($options);
        $result = count($result) ? $result[0] : null;
        return array(
            array(
                'success' => !is_null($result),
                'result' => $result
            ),
            !is_null($result) ? 200 : 404
        );
    }

    /**
     * Get index of items related to the given id.
     *
     * @param array $params
     * @return array
     */
    protected function _getRelatedResults(array $params) {
        // Check for existence of the subject record first, in order to return a 404 error
        list($subjectRecord, $httpCode) = $this->_getSingleResult($params);
        if (!$subjectRecord['success']) {
            return array(
                array(
                    'success' => false,
                    'result' => array()
                ),
                404
            );
        }

        $schema = new Garp_Content_Api_Rest_Schema('rest');
        $relation = $schema->getRelation($params['datatype'], $params['relatedType']);
        list($rule1, $rule2) = $relation->getRules($params['datatype']);

        $contentManager = $this->_getContentManager($relation->model);
        $options = $this->_extractOptionsForFetch($params);
        $options['query'][ucfirst($params['datatype']) . '.id'] = $params['id'];
        $options['bindingModel'] = $relation->getBindingModel()->id;
        $options['rule'] = $rule1;
        $options['rule2'] = $rule2;

        $records = $contentManager->fetch($options);
        return array(
            array(
                'success' => true,
                'result' => $records,
                'amount' => count($records),
                'total' => intval($contentManager->count($options))
            ),
            200
        );
    }

    protected function _normalizeModelName($modelName) {
        return 'Model_' . ucfirst($modelName);
    }

    protected function _requireDataType(array $params) {
        if (!array_get($params, 'datatype')) {
            throw new Garp_Content_Api_Rest_Exception(
                self::EXCEPTION_MISSING_MODEL
            );
        }
    }

    protected function _validatePostParams(array $params, array $postData) {
        if (array_get($params, 'id')) {
            throw new Garp_Content_Api_Rest_Exception(
                self::EXCEPTION_POST_WITH_ID
            );
        }
        if (empty($postData)) {
            throw new Garp_Content_Api_Rest_Exception(
                self::EXCEPTION_MISSING_POSTDATA
            );
        }
    }

    protected function _updateSingle(array $params, array $postData) {
        if (empty($postData)) {
            throw new Garp_Content_Api_Rest_Exception(
                self::EXCEPTION_MISSING_POSTDATA
            );
        }
        $model = $this->_normalizeModelName($params['datatype']);
        $contentManager = $this->_getContentManager($model);
        $contentManager->update(array_merge($postData, array('id' => $params['id'])));
    }

    protected function _addRelation(array $params, array $postData) {
        $options = $this->_getOptionsForRelationMutation(
            $params['datatype'],
            $params['relatedType'],
            $params['id']
        );
        $options['foreignKeys'] = array(
            array(
                'key' => $params['relatedId'],
                'relationMetadata' => $postData
            )
        );
        $contentManager = $this->_getContentManager($params['datatype']);
        $contentManager->relate($options);
    }

    protected function _removeRelation(array $params) {
        if (!array_get($params, 'relatedId')) {
            throw new Garp_Content_Api_Rest_Exception(
                self::EXCEPTION_MISSING_RELATED_ID
            );
        }
        $options = $this->_getOptionsForRelationMutation(
            $params['datatype'],
            $params['relatedType'],
            $params['id']
        );
        $options['foreignKeys'] = array(
            $params['relatedId']
        );
        $contentManager = $this->_getContentManager($params['datatype']);
        $contentManager->unrelate($options);
    }

    protected function _getOptionsForRelationMutation($model, $relatee, $id) {
        $schema = new Garp_Content_Api_Rest_Schema('rest');
        $relation = $schema->getRelation($model, $relatee);
        list($rule1, $rule2) = $relation->getRules($model);
        return array(
            'bindingModel' => $relation->getBindingModel()->id,
            'rule' => $rule1,
            'rule2' => $rule2,
            'primaryKey' => $id,
            'model' => $relation->model,
            'bidirectional' => $relation->isBidirectional()
        );
    }

    /**
     * Get related hasOne records and combine into a single response
     *
     * @param string $datatype
     * @param array $records
     * @param array $with
     * @return array The combined resultsets
     */
    protected function _combineRecords($datatype, array $records, $with) {
        $modelName = $this->_normalizeModelName($datatype);
        $rootModel = new $modelName;
        $schema = instance(new Garp_Content_Api_Rest_Schema('rest'))->getModelDetails($datatype);
        $hasOneRelations = array_filter(
            array_map(array_get('relation'), $schema['fields'])
        );
        // Check validity of 'with'
        $unknowns = array_filter($with, callRight(not('in_array'), $hasOneRelations));
        if (count($unknowns)) {
            $err = sprintf(
                Garp_Content_Api_Rest_Schema::EXCEPTION_RELATION_NOT_FOUND, $modelName,
                current($unknowns)
            );
            throw new Garp_Content_Api_Rest_Exception($err);
        }

        $self = $this;
        return array_reduce(
            $with,
            function ($acc, $cur) use ($rootModel, $records, $schema, $self) {
                // grab foreign key names from the relation
                $foreignKey = current(
                    array_filter(
                        $schema['fields'],
                        propertyEquals('relation', $cur)
                    )
                );

                // Grab foreign key values
                $foreignKeyValues = array_map(array_get($foreignKey['name']), $records);
                // No values to filter on? Bail.
                if (!count(array_filter($foreignKeyValues))) {
                    $acc[$cur] = array();
                    return $acc;
                }
                $foreignKeyValues = array_values(array_unique($foreignKeyValues));

                // Construct options object for manager
                $options = array(
                    'options'  => array('query' => array('id' => $foreignKeyValues)),
                    'datatype' => $foreignKey['model']
                );

                // fetch with options
                $acc[$cur] = array_get(current($self->_getIndex($options)), 'result');
                return $acc;
            },
            array($modelName => $records)
        );

    }

    protected function _getUrlsForOptions() {
        $config = Zend_Registry::get('config');
        return array(
            'web' => (string)new Garp_Util_FullUrl(array(array(), 'home')),
            'documents_upload' => (string)new Garp_Util_FullUrl(
                array(array('type' => Garp_File::TYPE_DOCUMENTS), 'upload')
            ),
            'images_upload' => (string)new Garp_Util_FullUrl(
                array(array('type' => Garp_File::TYPE_IMAGES), 'upload')
            ),
            'images_cdn' => new Garp_Util_AssetUrl('') . $config->cdn->path->upload->image,
            'documents_cdn' => new Garp_Util_AssetUrl('') . $config->cdn->path->upload->document
        );
    }

    protected function _getContentManager($model) {
        $contentManager = new Garp_Content_Manager($model);
        $contentManager->useJointView(false);
        return $contentManager;
    }
}
