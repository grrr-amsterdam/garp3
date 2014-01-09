<?php

class Garp_Cli_Command_Twitter extends Garp_Cli_Command_Twitter_Abstract {

	public function fetchConfig() {
		$search = array();
		$userTimeline = array();
		$userList = array();

		if (isset($this->config->twitter->search)) {
			foreach ($this->config->twitter->search as $query) {
				$search[] = $query;
			}
		}

		if (isset($this->config->twitter->userTimeline)) {
			foreach ($this->config->twitter->userTimeline as $screen_name) {
				$userTimeline[] = $screen_name;
			}
		}

		if (isset($this->config->twitter->userList)) {
			foreach ($this->config->twitter->userList as $name => $list) {
				$userList[] = array(
					'name'	=>	$name,
					'owner'	=>	$list->owner,
					'slug'	=>	$list->slug
				);
			}
		}

		return array(
			'search' => $search,
			'userTimeline' => $userTimeline,
			'userList' => $userList
		);
	}
}

# End of file