<?php
/**
 * Garp_Model_Behavior_Draftable
 * Handles 'draft' status and 'published' dates.
 * A table must have an 'online_status' column (TINYINT) and a 'published' column (DATETIME) to
 * work with this behavior.
 * The SELECT object is modified with every fetch() command to include the following WHERE clause:
 *
 * WHERE online_status = 1 AND (published IS NULL OR published <= NOW())
 *
 * All this is not applicable in the CMS context.
 *
 * @package Garp_Model_Behavior
 * @author Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Model_Behavior_Draftable extends Garp_Model_Behavior_Abstract {
    /**
     * Online status column
     *
     * @var string
     */
    const STATUS_COLUMN = 'online_status';

    /**
     * Published date column
     *
     * @var string
     */
    const PUBLISHED_COLUMN = 'published';

    /**
     * Human-readable status ints
     *
     * @var int
     */
    const OFFLINE = 0;
    const ONLINE = 1;

    /**
     * Wether to block offline items
     *
     * @var bool
     */
    protected $_blockOfflineItems = true;

    /**
     * Use this model alias
     *
     * @var string
     */
    protected $_modelAlias = '';

    /**
     * Wether to force this behavior (AKA also act in CMS or preview mode)
     *
     * @var string
     */
    protected $_force = false;

    /**
     * Configuration.
     *
     * @param array $config
     * @return void
     */
    protected function _setup($config) {
        if (!array_key_exists('draft_only', $config)) {
            $config['draft_only'] = false;
        }
        $this->_config = $config;
    }

    /**
     * Before fetch callback.
     * Adds the WHERE clause.
     *
     * @param array $args
     * @return void
     */
    public function beforeFetch(&$args) {
        $model = $args[0];
        $is_cms = $model->isCmsContext();
        $is_preview = $this->_isPreview() && Garp_Auth::getInstance()->isLoggedIn();

        $force = $this->_force;
        if (($is_cms || $is_preview) && !$force) {
            // don't use in the CMS, or in preview mode
            return;
        }

        $model = &$args[0];
        $select = &$args[1];

        if ($this->_blockOfflineItems) {
            $this->addWhereClause($model, $select);
        }
    }

    /**
     * Add the WHERE clause that keeps offline items from appearing in the results
     *
     * @param Garp_Model_Db $model
     * @param Zend_Db_Select $select
     * @return void
     */
    public function addWhereClause(&$model, Zend_Db_Select &$select) {
        $statusColumn = $model->getAdapter()->quoteIdentifier(self::STATUS_COLUMN);
        $publishedColumn = $model->getAdapter()->quoteIdentifier(self::PUBLISHED_COLUMN);

        if ($this->_modelAlias) {
            $modelAlias = $this->_modelAlias;
            $modelAlias = $model->getAdapter()->quoteIdentifier($modelAlias);
            $statusColumn = "$modelAlias.$statusColumn";
            $publishedColumn = "$modelAlias.$publishedColumn";
        }

        // Add online_status check
        $select->where($statusColumn . ' = ?', self::ONLINE);

        // Add published check
        if ($this->_config['draft_only']) {
            return;
        }

        $ini = Zend_Registry::get('config');
        $timezone = !empty($ini->resources->db->params->timezone) ?
            $ini->resources->db->params->timezone : null;
        $timecalc = '';
        if ($timezone == 'GMT' || $timezone == 'UTC') {
            $dstStart = strtotime('Last Sunday of March');
            $dstEnd   = strtotime('Last Sunday of October');
            $now      = time();
            $daylightSavingsTime = $now > $dstStart && $now < $dstEnd;

            $timecalc = '+ INTERVAL';
            if ($daylightSavingsTime) {
                $timecalc .= ' 2 HOUR';
            } else {
                $timecalc .= ' 1 HOUR';
            }
        }
        $select->where(
            $publishedColumn . ' IS NULL OR ' . $publishedColumn . ' <= NOW() ' . $timecalc
        );
    }

    /**
     * After insert callback.
     *
     * @param array $args
     * @return void
     */
    public function afterInsert(&$args) {
        $model = $args[0];
        $data = $args[1];
        $this->afterSave($model, $data);
    }

    /**
     * After update callback
     *
     * @param array $args
     * @return void
     */
    public function afterUpdate(&$args) {
        $model = $args[0];
        $data = $args[2];
        $this->afterSave($model, $data);
    }

    /**
     * After save callback, called by afterInsert and afterUpdate.
     * Sets an `at` job that clears the Static Page cache at the exact moment of the Published date.
     *
     * @param Garp_Model_Db $model
     * @param array $data
     * @return void
     */
    public function afterSave($model, $data) {
        // Check if the 'published column' is filled...
        if (empty($data[self::PUBLISHED_COLUMN])) {
            return;
        }

        // ...and that it's in the future
        $publishTime = strtotime($data[self::PUBLISHED_COLUMN]);
        if ($publishTime <= time()) {
            return;
        }

        $tags = array(get_class($model));
        $tags = array_merge($tags, $model->getBindableModels());
        $tags = array_unique($tags);
        Garp_Cache_Manager::scheduleClear($publishTime, $tags);
    }

    /**
     * Set blockOfflineItems
     *
     * @param bool $blockOfflineItems
     * @return $this
     */
    public function setBlockOfflineItems($blockOfflineItems) {
        $this->_blockOfflineItems = $blockOfflineItems;
        return $this;
    }

    /**
     * Get blockOfflineItems
     *
     * @return bool
     */
    public function getBlockOfflineItems() {
        return $this->_blockOfflineItems;
    }

    /**
     * Set modelAlias
     *
     * @param string $modelAlias
     * @return $this
     */
    public function setModelAlias($modelAlias) {
        $this->_modelAlias = $modelAlias;
        return $this;
    }

    /**
     * Get modelAlias
     *
     * @return string
     */
    public function getModelAlias() {
        return $this->_modelAlias;
    }

    /**
     * Set force
     *
     * @param bool $force
     * @return $this
     */
    public function setForce($force) {
        $this->_force = $force;
        return $this;
    }

    /**
     * Get force
     *
     * @return bool
     */
    public function getForce() {
        return $this->_force;
    }

    protected function _isPreview() {
        $currentRequest = Zend_Controller_Front::getInstance()->getRequest();
        $previewInGet = isset($_GET) && array_key_exists('preview', $_GET);
        $previewInRequest = $currentRequest &&
            array_key_exists('preview', $currentRequest->getParams());
        return $previewInGet || $previewInRequest;
    }
}
