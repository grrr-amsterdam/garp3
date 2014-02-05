<?php

require APPLICATION_PATH . '/../library/Garp/3rdParty/codebird/codebird.php';

class Garp_Social_Twitter {

	private $cb;

	public function __construct() {
		$this->config = Zend_Registry::get('config');

		\Codebird\Codebird::setConsumerKey($this->config->twitter->consumerKey, $this->config->twitter->consumerSecret);
		$this->cb = \Codebird\Codebird::getInstance();

		if (isset($this->config->twitter->bearerToken)) {
			$bearerToken = $this->config->twitter->bearerToken;
		} else {
			$bearerToken = $this->_fetchBearerToken();
		}

		\Codebird\Codebird::setBearerToken($bearerToken);
	}

	private function _fetchBearerToken() {
		$reply = $this->cb->oauth2_token();
		if ($reply->httpstatus == 200) {
			return $reply->access_token;
		}

		return FALSE;
	}

	public function update($config = NULL) {
		if (empty($config)) {
			$config = $this->fetchConfig();
		}

		$tweets = $this->fetchTweets($config);

		$this->_saveTweets($tweets);
	}

	public function fetchConfig() {
		if (isset($this->config->twitter->search)) {
			foreach ($this->config->twitter->search as $query) {
				$config['search'][] = $query;
			}
		}

		if (isset($this->config->twitter->userTimeline)) {
			foreach ($this->config->twitter->userTimeline as $screen_name) {
				$config['userTimeline'][] = $screen_name;
			}
		}

		if (isset($this->config->twitter->userList)) {
			foreach ($this->config->twitter->userList as $name => $list) {
				$config['userList'][] = array(
					'name'	=>	$name,
					'owner'	=>	$list->owner,
					'slug'	=>	$list->slug
				);
			}
		}

		return $config;
	}

	public function fetchTweets($config) {
		if (empty($config)) {
			return FALSE;
		}

		if (isset($config['search'])) {
			foreach ($config['search'] as $query) {
				$tweets['search'][$query] = $this->_searchTweets($query);
			}
		}

		if (isset($config['userTimeline'])) {
			foreach ($config['userTimeline'] as $screen_name) {
				$tweets['userTimeline'][$screen_name] = $this->_fetchUserTimeline($screen_name);
			}
		}

		if (isset($config['userList'])) {
			foreach ($config['userList'] as $list) {
				$tweets['userList'][$list['name']] = $this->_fetchUserList($list['owner'], $list['slug']);

			}
		}

		return $tweets;
	}

	private function _saveTweets($tweets) {
		$file = new Garp_File();
		$prefix = 'twitter';

		if (isset($tweets['search'])) {
			foreach ($tweets['search'] as $query => $result) {
				$file->store($prefix . '_' . $query . '_search.js', $this->_addCallback($result), TRUE);
			}
		}

		if (isset($tweets['userTimeline'])) {
			foreach ($tweets['userTimeline'] as $screen_name => $timeline) {
				$file->store($prefix . '_' . $screen_name . '_timeline.js', $this->_addCallback($timeline), TRUE);
			}
		}

		if (isset($tweets['userList'])) {
			foreach ($tweets['userList'] as $name => $list) {
				$file->store($prefix . '_' . $name . '_list.js', $this->_addCallback($list), TRUE);
			}
		}
	}

	private function _addCallback($data) {
		$json = json_encode($data);
		return 'window.onTwitterStreamLoaded && onTwitterStreamLoaded(' . $json .');';
	}

	private function _searchTweets($query) {
		$reply = $this->cb->search_tweets('q=' . $query, TRUE);
		if ($reply->httpstatus == 200) {
			return $reply->statuses;
		}

		return FALSE;
	}

	private function _fetchUserTimeline($screen_name) {
		$reply = $this->cb->statuses_userTimeline('screen_name=' . $screen_name, TRUE);
		if ($reply->httpstatus == 200) {
			return $this->_getTweets($reply);
		}

		return FALSE;
	}

	private function _fetchUserList($screen_name, $slug) {
		$reply = $this->cb->lists_statuses('owner_screen_name=' . $screen_name . '&slug=' . $slug, TRUE);
		if ($reply->httpstatus == 200) {
			return $this->_getTweets($reply);
		}

		return FALSE;
	}

	private function _getTweets($data) {
		foreach ($data as $status) {
			if (isset($status->text)) {
				$statuses[] = $status;
			}
		}

		return $statuses;
	}
}
