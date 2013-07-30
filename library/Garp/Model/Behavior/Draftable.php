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
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class Garp_Model_Behavior_Draftable extends Garp_Model_Behavior_Abstract {
	/**
 	 * Online status column
 	 * @var String
 	 */
	const STATUS_COLUMN = 'online_status';

	/**
 	 * Published date column
 	 * @var String
 	 */
	const PUBLISHED_COLUMN = 'published';

	/**
 	 * Human-readable status ints
 	 * @var Int
 	 */
	const OFFLINE = 0;
	const ONLINE = 1;

	/**
 	 * Wether to block offline items
 	 * @var Boolean
 	 */
	protected $_blockOfflineItems = true;

	/**
	 * Configuration.
	 * @return Void
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
	 * @param Array $args
	 * @return Void
	 */
	public function beforeFetch(&$args) {
		$is_cms = Zend_Registry::isRegistered('CMS') && Zend_Registry::get('CMS');
		$is_preview = isset($_GET) && array_key_exists('preview', $_GET) && Garp_Auth::getInstance()->isLoggedIn();
		if ($is_cms || $is_preview) {
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
 	 * @param Garp_Model_Db $model
 	 * @param Zend_Db_Select $select
 	 * @return Void
 	 */
	public function addWhereClause(&$model, Zend_Db_Select &$select) {
		$statusColumn = $model->getAdapter()->quoteIdentifier(self::STATUS_COLUMN);
		$publishedColumn = $model->getAdapter()->quoteIdentifier(self::PUBLISHED_COLUMN);

		// Add online_status check
		$select->where($statusColumn.' = ?', self::ONLINE);

		// Add published check
		if ($this->_config['draft_only']) {
			return;
		}
		$ini = Zend_Registry::get('config');
		$timezone = !empty($ini->resources->db->params->timezone) ? $ini->resources->db->params->timezone : null;
		$timecalc = '';
		if ($timezone == 'GMT') {
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
		$select->where($publishedColumn.' IS NULL OR '.$publishedColumn.' <= NOW() '.$timecalc);
	}		

	/**
 	 * After insert callback.
 	 * @param Array $args
 	 * @return Void
 	 */
	public function afterInsert(&$args) {
		$model = $args[0];
		$data = $args[1];
		$this->afterSave($model, $data);
	}

	/**
 	 * After update callback
 	 * @param Array $args
 	 * @return Void
 	 */
	public function afterUpdate(&$args) {
		$model = $args[0];
		$data = $args[2];
		$this->afterSave($model, $data);
	}

	/**
 	 * After save callback, called by afterInsert and afterUpdate.
 	 * Sets an `at` job that clears the Static Page cache at the exact moment of the Published date.
 	 * @param Garp_Model_Db $model
 	 * @param Array $data
 	 * @return Void
 	 */
	public function afterSave($model, $data) {
		// Check if the 'published column' is filled...
		if (!empty($data[self::PUBLISHED_COLUMN])) {
			$publishTime = strtotime($data[self::PUBLISHED_COLUMN]);
			// ...and that it's in the future
			if ($publishTime > time()) {
				$tags = array(get_class($model));
				$tags = array_merge($tags, $model->getBindableModels());
				$tags = array_unique($tags);
				Garp_Cache_Purgatory::scheduleClear($publishTime, $tags);
			}
		}
	}

	/**
	 * Set blockOfflineItems
	 * @param Boolean blockOfflineItems
	 * @param Mixed $value
	 * @return $this
	 */
	public function setBlockOfflineItems($blockOfflineItems) {
		$this->_blockOfflineItems = $blockOfflineItems;
		return $this;
	}

	/**
	 * Get blockOfflineItems
	 * @return Boolean
	 */
	public function getBlockOfflineItems() {
		return $this->_blockOfflineItems;
	}
	
}
