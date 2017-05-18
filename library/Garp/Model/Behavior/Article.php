<?php
/**
 * Garp_Model_Behavior_Article
 * Wrapper around "article" style functionality. Provides rich magazine-type layouts.
 *
 * @package Garp_Model_Behavior
 * @author Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Model_Behavior_Article extends Garp_Model_Behavior_Abstract {
    /**
     * Queue of chapters. These are queued beforeSave,
     * and popped from the queue afterSave.
     *
     * @var array
     */
    protected $_queuedChapters = array();

    /**
     * Config values
     *
     * @var Garp_Util_Configuration
     */
    protected $_config;

    /**
     * Configure the behavior
     *
     * @param array $config
     * @return void
     */
    protected function _setup($config) {
        $config = new Garp_Util_Configuration($config);
        $config->obligate('contentTypes');
        $this->_config = $config;
    }


    /**
     * Bind all the Chapters and ContentNodes to fetch the complete Article.
     *
     * @param Garp_Model_Db $model
     * @return void
     */
    public function bindWithChapters(Garp_Model_Db &$model) {
        $chapterModel = new Model_Chapter();
        $contentNodeModel = new Model_ContentNode();
        $chapterModel->bindModel(
            'content', array('modelClass' => $contentNodeModel)
        );

        foreach ($this->_config['contentTypes'] as $chapterType) {
            $chapterAlias = $this->_extractContentTypeAlias($chapterType);
            $options = $this->_extractContentTypeBindOptions($chapterType, $contentNodeModel);
            $contentNodeModel->bindModel($chapterAlias, $options);
        }

        $model->bindModel(
            'chapters', array('modelClass' => $chapterModel)
        );
    }

    /**
     * An article is nothing without its Chapters. Before every fetch
     * we make sure the chapters are fetched right along, at least in
     * the CMS.
     *
     * @param array $args Event listener parameters
     * @return void
     */
    public function beforeFetch(&$args) {
        if (Zend_Registry::isRegistered('CMS') && Zend_Registry::get('CMS')) {
            $model = &$args[0];
            $this->bindWithChapters($model);
        }
    }

    /**
     * AfterFetch callback, reforms the bound Chapters into a
     * more concise collection.
     *
     * @param array $args Event listener parameters
     * @return void
     */
    public function afterFetch(&$args) {
        $results = &$args[1];
        $iterator = new Garp_Db_Table_Rowset_Iterator(
            $results,
            array($this, 'convertArticleLayout')
        );
        $iterator->walk();
        return true;
    }

    /**
     * BeforeInsert event listener.
     *
     * @param array $args Event listener parameters
     * @return Void
     */
    public function beforeInsert(&$args) {
        $data = &$args[1];
        $this->_beforeSave($data);
    }

    /**
     * BeforeUpdate event listener.
     *
     * @param array $args Event listener parameters
     * @return Void
     */
    public function beforeUpdate(&$args) {
        $data = &$args[1];
        $this->_beforeSave($data);
    }

    /**
     * AfterInsert event listener.
     *
     * @param array $args Event listener parameters
     * @return void
     */
    public function afterInsert(&$args) {
        $model      = &$args[0];
        $data       = &$args[1];
        $primaryKey = &$args[2];
        $this->_afterSave($model, $primaryKey);
    }

    /**
     * AfterUpdate event listener.
     *
     * @param array $args Event listener parameters
     * @return void
     */
    public function afterUpdate(&$args) {
        $model = $args[0];
        $where = $args[3];
        $primaryKey = $model->extractPrimaryKey($where);
        $id = $primaryKey['id'];
        $this->_afterSave($model, $id);
    }

    /**
     * Generic beforeSave handler, called by beforeInsert and beforeUpdate
     *
     * @param array $data
     * @return void
     */
    protected function _beforeSave(&$data) {
        // Make sure chapters are not saved.
        if (!empty($data['chapters'])) {
            // Save chapters until after the Article is inserted, because we need the primary key.
            if (is_string($data['chapters'])) {
                $data['chapters'] = Zend_Json::decode($data['chapters']);
            }
            $this->_queuedChapters = $data['chapters'];
        }
        unset($data['chapters']);
    }

    /**
     * Generic afterSave handler, called from afterInsert and afterUpdate
     *
     * @param Garp_Model_Db $model The subject model
     * @param int $id The id of the involved record
     * @return void
     */
    protected function _afterSave(Garp_Model_Db $model, $id) {
        if (!empty($this->_queuedChapters)) {
            $this->relateChapters($this->_queuedChapters, $model, $id);
            // Reset queue.
            $this->_queuedChapters = array();
        }
    }

    /**
     * Convert the article row to a more concise format.
     * Calls self::_convertChapterLayout().
     *
     * @param Garp_Db_Table_Row $result
     * @return void
     */
    public function convertArticleLayout($result) {
        if (isset($result->chapters)) {
            $result->chapters = array_map(
                array($this, '_convertChapterLayout'),
                $result->chapters->toArray()
            );
        }
    }

    /**
     * Convert bound Chapters to a more concise format.
     *
     * @param array $chapterRow
     * @return array
     */
    protected function _convertChapterLayout(array $chapterRow) {
        $chapter = array();
        $chapter['type'] = $chapterRow['type'];
        if (!isset($chapterRow['content'])) {
            return $chapter;
        }
        $chapter['content'] = array_map(
            array($this, '_convertContentNodeLayout'),
            $chapterRow['content']
        );
        return $chapter;
    }

    /**
     * Convert bound ContentNodes to a more concise format.
     *
     * @param array $contentNodeRow
     * @return array
     */
    protected function _convertContentNodeLayout(array $contentNodeRow) {
        $contentTypes = $this->_config['contentTypes'];
        $contentNode = array();
        foreach ($contentTypes as $contentType) {
            $contentType = $this->_extractContentTypeAlias($contentType);
            if ($contentNodeRow[$contentType]) {
                $modelName = explode('_', $contentType);
                $modelName = array_pop($modelName);
                $contentNode['model'] = $modelName;
                $contentNode['type']  = $contentNodeRow['type'];
                $contentNode['data']  = $contentNodeRow[$contentType];
                $contentNode['columns'] = $contentNodeRow['columns'];
                $contentNode['classes'] = $contentNodeRow['classes'];
                break;
            }
        }
        return $contentNode;
    }


    /**
     * Relate Chapters.
     * Called after insert and after update.
     *
     * @param array $chapters
     * @param Garp_Model_Db $model The subject model
     * @param int $articleId The id of the involved article
     * @return void
     */
    public function relateChapters(array $chapters, Garp_Model_Db $model, $articleId) {
        // Start by unrelating all chapters
        Garp_Content_Relation_Manager::unrelate(
            array(
                'modelA' => $model,
                'modelB' => 'Model_Chapter',
                'keyA'   => $articleId,
            )
        );
        // Reverse order since the Weighable behavior sorts chapter by weight DESC,
        // giving each new chapter the highest weight.
        $chapters = array_reverse($chapters);
        foreach ($chapters as $chapterData) {
            $chapterData = $this->_getValidChapterData($chapterData);

            /**
             * Insert a new chapter.
             * The chapter will take care of storing and relating the
             * content nodes.
             */
            $chapterModel = new Model_Chapter();
            $chapterId = $chapterModel->insert(
                array(
                    'type'    => $chapterData['type'],
                    'content' => $chapterData['content'],
                )
            );

            Garp_Content_Relation_Manager::relate(
                array(
                    'modelA' => $model,
                    'modelB' => 'Model_Chapter',
                    'keyA'   => $articleId,
                    'keyB'   => $chapterId,
                )
            );
        }
    }

    /**
     * Convert chapter to Garp_Util_Configuration and validate its keys.
     *
     * @param array|Garp_Util_Configuration $chapterData
     * @return Garp_Util_Configuration
     */
    protected function _getValidChapterData($chapterData) {
        $chapterData = $chapterData instanceof Garp_Util_Configuration ?
            $chapterData : new Garp_Util_Configuration($chapterData);
        $chapterData
            ->obligate('content')
            ->setDefault('type', '');
        return $chapterData;
    }

    protected function _extractContentTypeAlias($chapterType) {
        if (is_string($chapterType)) {
            return $chapterType;
        }
        if (!isset($chapterType['model'])) {
            throw new Exception('Required key "model" not found');
        }
        return $chapterType['model'];
    }

    protected function _extractContentTypeBindOptions($chapterType, $model) {
        if (is_string($chapterType)) {
            return array('modelClass' => 'Model_' . $chapterType);
        }
        $out = array();
        if (isset($chapterType['i18n']) && $chapterType['i18n']) {
            if (!isset($chapterType['model'])) {
                throw new Exception('Required key "model" not found');
            }
            if (Zend_Registry::isRegistered('CMS') && Zend_Registry::get('CMS')) {
                return array('modelClass' => 'Model_' . $chapterType['model']);
            }
            $out['modelClass'] = instance(new Garp_I18n_ModelFactory())
                ->getModel($chapterType['model']);
            // Make sure the localised relation exists in the referenceMap
            $localiser = new Garp_Model_ReferenceMapLocalizer($model);
            $localiser->populate('Model_' . $chapterType['model']);
        }
        return $out;
    }

}
