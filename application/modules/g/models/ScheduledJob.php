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
				->where('at > ?', $lastCheckIn)
				->where('at < NOW()')
		);
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
