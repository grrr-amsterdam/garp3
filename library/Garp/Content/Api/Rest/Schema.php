<?php
/**
 * Garp_Content_Api_Rest_Schema
 * class description
 *
 * @package Garp_Content_Api_Rest_Schema
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Content_Api_Rest_Schema {
    const EXCEPTION_MODEL_NOT_FOUND = 'Unknown model %s';
    const EXCEPTION_RELATION_NOT_FOUND = '%s is not related to %s';

    protected $_apiRoute;

    public function __construct($apiRoute) {
        $this->_apiRoute = $apiRoute;
    }

    public function getModelPaths() {
        $models = $this->_getVisibleModels();
        return array_map(array($this, 'getModelConfig'), $models);
    }

    public function getModelDetails($modelName) {
        $model = $this->_getModelByName($modelName);
        $config = $this->getModelConfig($model);
        $config['order'] = $model->order;
        $config['name'] = $modelName;
        // getListFieldNames());
        $config['listFields'] = $this->_getListFields($model);
        $config['label'] = $model->label;
        $config['description'] = $model->description;
        $config['creatable'] = $model->creatable;
        $config['deletable'] = $model->deletable;
        $config['route'] = $model->route;
        $config['fields'] = array_values($this->_getFields($model));
        $config['relations'] = $this->_getRelationSchema($model);
        $config['quickAddable'] = $model->quickAddable;
        return $config;
    }

    public function getModelConfig($model) {
        return array(
            'label' => $model->label,
            'root' => (string)new Garp_Util_FullUrl(
                array(array('datatype' => $model->id), $this->_apiRoute)
            )
        );
    }

    public function getRelation($model, $relatedModel) {
        $model = $this->_getModelByName($model);
        $modelRelations = $this->_getRelationSchema($model);
        if (!in_array($relatedModel, $modelRelations)) {
            throw new Garp_Content_Api_Rest_Exception(
                sprintf(self::EXCEPTION_RELATION_NOT_FOUND, $model->id, $relatedModel)
            );
        }
        return $model->relations->getRelation($relatedModel);
    }

    protected function _getRelationSchema($model) {
        return array_merge(
            array_keys($model->relations->getRelations('type', 'hasMany')),
            array_keys($model->relations->getRelations('type', 'hasAndBelongsToMany'))
        );
    }

    protected function _getVisibleModels() {
        return array_filter(
            (array)Garp_Spawn_Model_Set::getInstance(),
            f\prop('visible')
        );
    }

    protected function _getModelByName($modelName) {
        $models = $this->_getVisibleModels();
        $modelsWithName = array_filter($models, propertyEquals('id', $modelName));
        if (!count($modelsWithName)) {
            throw new Garp_Content_Api_Rest_Exception_ModelNotFound(
                sprintf(self::EXCEPTION_MODEL_NOT_FOUND, $modelName)
            );
        }
        return current($modelsWithName);
    }

    protected function _getFields($model) {
        return array_map(
            function ($field) {
                return get_object_vars($field);
            },
            $model->fields->getFields() //('editable', true)
        );
    }

    protected function _getHasOneColumns($model) {
        $hasOneRelations = $model->relations->getRelations('type', 'hasOne');
        $hasOneForeignKeyTemplate = array(
            'type' => 'relation',
        );
        $hasOneColumns = array_map(
            function ($relation) use ($hasOneForeignKeyTemplate) {
                return array_merge(
                    $hasOneForeignKeyTemplate,
                    array(
                        'relation' => $relation->name,
                        'name' => $relation->column,
                        'label' => $relation->label,
                        'required' => $relation->required,
                        'model' => $relation->model
                    )
                );
            },
            $hasOneRelations
        );
        return $hasOneColumns;
    }

    protected function _getListFields($model) {
        return array_merge(
            array_values($model->fields->listFieldNames),
            array('author_id')
        );
    }
}
