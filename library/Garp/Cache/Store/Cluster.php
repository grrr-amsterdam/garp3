<?php
/**
 * Garp_Cache_Store_Cluster
 * Functionality to clear cache in a server cluster
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Cache
 */
class Garp_Cache_Store_Cluster {
	public $clearedTags;

	protected $_lastCheckIn;
	
	protected $_serverId;



	public function executeDueJobs() {
		$serverRow = $this->_checkInServer();

		if (count($serverRow)) {
			//	server was previously registered
			$jobs = $this->_findDueJobs($serverRow->modified, $serverRow->id);
			if (count($jobs)) {
				if ($this->_containsGeneralClearJob($jobs)) {
					Garp_Cache_Manager::purge(array(), false);
					$this->clearedTags = array();
				} else {
					$tags = $this->_getTagsFromJobs($jobs);
					Garp_Cache_Manager::purge($tags, false);
					$this->clearedTags = $tags;
				}
			} else {
				$this->clearedTags = false;
			}
		} else {
			Garp_Cache_Manager::purge(array(), false);
			$this->clearedTags = array();
		}
	}


	static public function createJob(Array $tags = array()) {
		$serverRow = self::_checkInServer();

		$jobModel = new Model_ClusterClearCacheJob();
		$jobRow = $jobModel->createRow();

		$jobRow->creator_id = $serverRow->id;
		$jobRow->tags = serialize($tags);

		$jobRow->save();
	}


	protected function _getTagsFromJobs(Garp_Db_Table_Rowset $jobs) {
		$tags = array();

		foreach ($jobs as $job) {
			$tagsPerJob = unserialize($job->tags);

			if (!empty($tagsPerJob)) {
				foreach ($tagsPerJob as $tag) {
					$tags[$tag] = true;
				}
			}
		}

		return array_keys($tags);
	}


	protected function _containsGeneralClearJob(Garp_Db_Table_Rowset $jobs) {
		foreach ($jobs as $job) {
			$tags = unserialize($job->tags);
			if (empty($tags))
				return true;
		}
		
		return false;
	}


	/**
	 * Registers this server node in the ClusterServer table.
	 * @return Garp_Db_Table_Row Database row of this server node
	 */
	static protected function _checkInServer() {
		$hostname = gethostname();

		$serverRow = self::_fetchServerRow();
		if (!$serverRow) {
			$serverModel = new Model_ClusterServer();
			$serverRow = $serverModel->createRow();
		}

		$serverRow->hostname = $hostname;
		$serverRow->save();

		return $serverRow;
	}
	
	
	protected function _findDueJobs($lastCheckIn, $serverId) {
		$jobModel = new Model_ClusterClearCacheJob();
		return $jobModel->fetchAll(
			$jobModel->select()
				->where('id != ?', $serverId)
				->where('created > ?', $lastCheckIn)
		);
	}


	static protected function _fetchServerRow() {
		$hostname = gethostname();

		$serverModel = new Model_ClusterServer();
		return $serverModel->fetchRow(
			$serverModel->select()
				->where('hostname = ?', $hostname)
		);
	}
}