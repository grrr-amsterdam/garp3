<?php

require APPLICATION_PATH . '/../library/Garp/3rdParty/codebird/codebird.php';

abstract class Garp_Cli_Command_Twitter_Abstract extends Garp_Cli_Command implements Garp_Cli_Command_Twitter_Interface {

	private $cb;

	public function __construct() {
		$this->config = Zend_Registry::get('config');

		\Codebird\Codebird::setConsumerKey($this->config->twitter->consumerKey, $this->config->twitter->consumerSecret);
		$this->cb = \Codebird\Codebird::getInstance();

		if (isset($this->config->twitter->bearerToken)) {
			$bearerToken = $this->config->twitter->bearerToken;
		} else {
			$bearerToken = $this->_fetchBearerToken();
			Garp_Cli::lineOut("Bad developer, you're letting me fetch a bearerToken every single time.");
			Garp_Cli::lineOut("Please configure one in application/configs/app.ini as twitter.bearerToken!");
			Garp_Cli::lineOut("The current bearerToken is: " . $bearerToken);
		}

		\Codebird\Codebird::setBearerToken($bearerToken);
	}

	private function _fetchBearerToken() {
		$reply = $this->cb->oauth2_token();
		if ($reply->httpstatus == 200) {
			return $reply->access_token;
		}

		Garp_Cli::lineOut("I can't even fetch a bearerToken around here.");
		Garp_Cli::lineOut("Probably due your internet connection or bad consumer tokens.");
		Garp_Cli::lineOut("Error: " . $reply->errors[0]->message);
		exit;
	}

	public function update() {
		$config = $this->fetchConfig();
		$tweets = $this->fetchTweets($config);

		$this->_saveTweets($tweets);
	}

	public abstract function fetchConfig();

	public function fetchTweets($config) {
		$search = array();
		$userTimeline = array();
		$userList = array();

		foreach ($config['search'] as $query) {
			$search[$query] = $this->_searchTweets($query);
		}

		foreach ($config['userTimeline'] as $screen_name) {
			$userTimeline[$screen_name] = $this->_fetchUserTimeline($screen_name);
		}

		foreach ($config['userList'] as $list) {
			$userList[$list['name']] = $this->_fetchUserList($list['owner'], $list['slug']);
		}

		return array(
			'search' => $search,
			'userTimeline' => $userTimeline,
			'userList' => $userList
		);
	}

	private function _saveTweets($tweets) {
		$file = new Garp_File();

		foreach ($tweets['search'] as $query => $result) {
			$file->store($query . '_search.json', json_encode($result), TRUE);
		}

		foreach ($tweets['userTimeline'] as $screen_name => $timeline) {
			$file->store($screen_name . '_timeline.json', json_encode($timeline), TRUE);
		}

		foreach ($tweets['userList'] as $name => $list) {
			$file->store($name . '_list.json', json_encode($list), TRUE);
		}

		Garp_Cli::lineOut("Done.");
	}

	private function _searchTweets($query) {
		$reply = $this->cb->search_tweets('q=' . $query, TRUE);
		if ($reply->httpstatus == 200) {
			return $reply->statuses;
		}

		Garp_Cli::lineOut("Error: " . $reply->errors[0]->message);
		return FALSE;
	}

	private function _fetchUserTimeline($screen_name) {
		$reply = $this->cb->statuses_userTimeline('screen_name=' . $screen_name, TRUE);
		if ($reply->httpstatus == 200) {
			return $this->_getTweets($reply);
		}

		Garp_Cli::lineOut("Error: " . $reply->errors[0]->message);
		return FALSE;
	}

	private function _fetchUserList($screen_name, $slug) {
		$reply = $this->cb->lists_statuses('owner_screen_name=' . $screen_name . '&slug=' . $slug, TRUE);
		if ($reply->httpstatus == 200) {
			return $this->_getTweets($reply);
		}

		Garp_Cli::lineOut("Error: " . $reply->errors[0]->message);
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

# End of file