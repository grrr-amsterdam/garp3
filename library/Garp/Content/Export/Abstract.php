<?php

use function Garp\__;

/**
 * Garp_Content_Export_Abstract
 * Blueprint for content exporters
 *
 * @package Garp_Content_Export
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
abstract class Garp_Content_Export_Abstract {
    const EXCEPTION_INVALID_CONFIG
        = 'Invalid paging configuration given. Possible options: "page", "from", "to".';

    /**
     * File extension
     *
     * @var string
     */
    protected $_extension = '';

    /**
     * Return the bytes representing the export format (for instance, binary code
     * describing a PDF or Excel file). These will be offered to download.
     *
     * @param Garp_Util_Configuration $params Various parameters describing which content to export
     * @return string
     */
    public function getOutput(Garp_Util_Configuration $params) {
        $mem = new Garp_Util_Memory();
        $mem->useHighMemory();

        $params->setDefault('rule', null)
            ->setDefault('rule2', null);

        $filter = array();
        if (array_key_exists('filter', $params) && $params['filter']) {
            $filter = urldecode($params['filter']);
            $filter = Zend_Json::decode($params['filter']);
        }
        $fetchOptions = array(
            'query' => $filter,
            'rule'  => $params['rule'],
            'rule2' => $params['rule2'],
        );

        if (!empty($params['fields'])) {
            $fields = is_array($params['fields']) ? $params['fields'] :
                explode(',', $params['fields']);
            $fetchOptions['fields'] = array_combine($fields, $fields);
        }

        if (isset($params['sortField']) && isset($params['sortDir'])) {
            $fetchOptions['sort'] = array($params['sortField'] . ' ' . $params['sortDir']);
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
                throw new Garp_Content_Exception(self::EXCEPTION_INVALID_CONFIG);
            }
            break;
        }

        $fetchOptions['filterForeignKeys'] = true;

        $className = Garp_Content_Api::modelAliasToClass($params['model']);
        $model = new $className;
        $this->_bindModels($model);

        // Allow the model or its observers to modify the fetchOptions
        $model->notifyObservers('beforeExport', array(&$fetchOptions));

        $manager = new Garp_Content_Manager($model);
        $data = $manager->fetch($fetchOptions);
        $data = (array)$data;

        // Allow the model or its observers to modify the data
        $model->notifyObservers('afterExport', array(&$data, &$fetchOptions));

        if (empty($data)) {
            $data = array(
                array('message' => __('no results found'))
            );
        }
        $humanizedData = $this->_humanizeData($data, $model);
        $formattedData = $this->format($model, $humanizedData);
        return $formattedData;
    }


    /**
     * Generate a filename for the exported text file
     *
     * @param Garp_Util_Configuration $params
     * @return string
     */
    public function getFilename(Garp_Util_Configuration $params) {
        $className = Garp_Content_Api::modelAliasToClass($params['model']);
        $model = new $className();
        $filename  = 'export_';
        $filename .= $model->getName();
        $filename .= '_' . date('Y_m_d');
        $filename .= '.';
        $filename .= $this->_extension;
        return $filename;
    }


    /**
     * Format a recordset
     *
     * @param string $model The exported model. Formatters may want additional metadata from this.
     * @param array $rowset
     * @return string
     */
    abstract public function format(Garp_Model $model, array $rowset);


    /**
     * Translate the columns of a record into the human-friendly versions used
     * in the CMS
     *
     * @param array $data
     * @param Garp_Model_Db $model
     * @return array
     */
    protected function _humanizeData($data, Garp_Model_Db $model) {
        $humanizedData = array();
        foreach ($data as $i => $datum) {
            if (!is_array($datum)) {
                $humanizedData[$i] = $datum;
                continue;
            }
            foreach ($datum as $column => $value) {
                $field = $model->getFieldConfiguration($column);
                if ($field['type'] === 'checkbox') {
                    $value = $value ? __('yes') : __('no');
                }
                $alias = $column;
                if ($field) {
                    $alias = $field['label'];
                }

                $alias = ucfirst(__($alias));
                if (is_array($value) && $this->_isMultilingualArray($value)) {
                    // special case: we convert the language keys to new columns in the output
                    foreach ($value as $key => $data) {
                        $i18n_alias = "$alias ($key)";
                        $humanizedData[$i][$i18n_alias] = $data;
                    }
                    // Continue so we don't add duplicate data
                    continue;
                } elseif (is_array($value)) {
                    // OMG recursion!
                    $value = $this->_humanizeData($value, $model);
                }
                $humanizedData[$i][$alias] = $value;
            }
        }
        return $humanizedData;
    }

    /**
     * Humanize a multilingual data array
     *
     * @param array $value
     * @return string
     */
    protected function _humanizeMultilingualData(array $value) {
        $out = array();
        foreach ($value as $key => $data) {
            $out[] = "[$key]: $data";
        }
        return implode(" - ", $out);
    }

    /**
     * Check if value is a multilingual array.
     *
     * @param mixed $value
     * @return bool
     */
    protected function _isMultilingualArray($value) {
        if (!is_array($value)) {
            return false;
        }
        $locales = Garp_I18n::getLocales();
        $keys = array_keys($value);
        sort($locales);
        sort($keys);

        return $locales === $keys;
    }

    /**
     * Bind all HABTM related models so they, too, get exported
     *
     * @param Garp_Model_Db $model
     * @return void
     */
    protected function _bindModels(Garp_Model_Db $model) {
        // Add HABTM related records
        $relations = $model->getConfiguration('relations');
        foreach ($relations as $key => $config) {
            if ($config['type'] !== 'hasAndBelongsToMany' && $config['type'] !== 'hasMany') {
                continue;
            }
            $otherModelName = 'Model_' . $config['model'];
            $otherModel = new $otherModelName();
            $multilingual = false;
            $modelFactory = new Garp_I18n_ModelFactory();

            if ($otherModel->getObserver('Translatable')) {
                $otherModel = $modelFactory->getModel($otherModel);
                $multilingual = true;
            }

            $otherModelAlias = $otherModel->getName();
            $bindingModel = null;
            if ($config['type'] === 'hasAndBelongsToMany') {
                $bindingModelName = 'Model_' . $config['bindingModel'];
                $bindingModel = new $bindingModelName;
                if ($multilingual) {
                    $refmapLocaliser = new Garp_Model_ReferenceMapLocalizer($bindingModel);
                    $refmapLocaliser->populate($otherModelName);
                }
                $otherModelAlias = 'm';
            }

            $labelFields = $otherModel->getListFields();
            $prefixedLabelFields = array();
            foreach ($labelFields as $labelField) {
                $prefixedLabelFields[] = "$otherModelAlias.$labelField";
            }
            $labelFields = 'CONCAT_WS(", ", ' . implode(', ', $prefixedLabelFields) . ')';

            // If the Translatable behavior would be effective,
            // the output would be in a localized array, which is overkill for this
            // purpose.
            $otherModel->unregisterObserver('Translatable');

            $options = array(
                'bindingModel' => $bindingModel,
                'modelClass' => $otherModel,
                'conditions' => $otherModel->select()
                    ->setIntegrityCheck(false)
                    ->from(
                        array($otherModelAlias => $otherModel->getName()),
                        array($config['label'] => $labelFields)
                    )
                    ->order("$otherModelAlias.id")
            );
            $model->bindModel($config['label'], $options);
        }
    }
}
