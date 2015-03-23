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
}
