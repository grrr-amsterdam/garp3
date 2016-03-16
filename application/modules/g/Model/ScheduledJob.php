<?php
class G_Model_ScheduledJob extends Model_Base_ScheduledJob {
	public function init() {
		parent::init();

		$this->unregisterObserver('Cachable');
		$this->unregisterObserver('Authorable');
	}

	public function fetchDue($serverId, $lastCheckIn) {
		return $this->fetchAll(
			$this->select()
				->where('`at` >= ?', $lastCheckIn)
				->where('`at` <= ?', date('Y-m-d H:i:s'))
		);
	}

	// Overwrite insert in order to ignore duplicates: they're harmless
	public function insert(array $data) {
		try {
			$pkData = parent::insert($data);
		} catch (Zend_Db_Statement_Exception $e) {
			if (strpos($e->getMessage(), 'Duplicate entry') === false ||
				strpos($e->getMessage(), 'checksum_unique') === false) {
				throw $e;
			}
			// @todo Return original primary key?
			// This would require a second round-trip to the database...
			return null;
		}
		return $pkData;
	}

	public function beforeInsert(&$args) {
		$data  = &$args[1];
		$this->_addChecksum($data);
	}

	public function beforeUpdate(&$args) {
		$data  = &$args[1];
		$this->_addChecksum($data);
	}

	protected function _addChecksum(&$data) {
		if (!isset($data['at']) || !isset($data['command'])) {
			return;
		}
		$data['checksum'] = md5($data['at'] . ': ' . $data['command']);
	}
}
