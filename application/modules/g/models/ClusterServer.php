<?php
class G_Model_ClusterServer extends Model_Base_ClusterServer {
	public function init() {
		parent::init();

		$this->unregisterObserver('Cachable');
		$this->unregisterObserver('Authorable');
	}


	/**
	 * Registers this server node in the ClusterServer table, and returns information about the current server.
	 * @return Array Numeric array, containing the serverId and the last check-in time.
	 */
	public function checkIn() {
		$now = date('Y-m-d H:i:s');
		$serverRow = $this->_fetchServerRow();

		if (!$serverRow) {
			$serverRow = $this->createRow();
			$serverRow->hostname = gethostname();
			$lastCheckIn = $now;
		} else {
			$lastCheckIn = $serverRow->modified;
		}

		//	set the modified date manually, to make sure the record is updated.
		$serverRow->modified = $now;
		$serverId = $serverRow->save();

		return array(
			$serverId,
			$lastCheckIn
		);
	}
	
	
	public function fetchServerId() {
		if ($serverRow = $this->_fetchServerRow()) {
			return $serverRow->id;
		}
	}
	
	
	protected function _fetchServerRow() {
		return $this->fetchRow(
			$this->select()->where('hostname = ?', gethostname())
		);
	}
}