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



	/**
	 * @param Int $serverId Database id of the current server in the cluster
	 * @param String $lastCheckIn MySQL datetime that represents the last check-in time of this server
	 */
	public function executeDueJobs($serverId, $lastCheckIn) {
		//	if the last check-in was more than two hours ago, first clear the cache.
		if ((time() - strtotime($lastCheckIn)) > (60 * 60 * 2)) {
			Garp_Cache_Purgatory::purge(array(), false);
			$this->clearedTags = array();
		} else {
			$clusterClearCacheJobModel = new Model_ClusterClearCacheJob();
			$jobs = $clusterClearCacheJobModel->fetchDue($serverId, $lastCheckIn);

			if (count($jobs)) {
				if ($this->_containsGeneralClearJob($jobs)) {
					Garp_Cache_Purgatory::purge(array(), false);
					$this->clearedTags = array();
				} else {
					$tags = $this->_getTagsFromJobs($jobs);
					Garp_Cache_Purgatory::purge($tags, false);
					$this->clearedTags = $tags;
				}
			} else {
				$this->clearedTags = false;
			}
		}
	}


	static public function createJob(Array $tags = array()) {
		$clusterServerModel = new Model_ClusterServer();
		if (!($serverId = $clusterServerModel->fetchServerId())) {
			list($serverId, $lastCheckIn) = $clusterServerModel->checkIn();
		}

		$jobModel = new Model_ClusterClearCacheJob();
		$jobModel->create($serverId, $tags);
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
}
