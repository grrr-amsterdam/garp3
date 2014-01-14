<?php

class Garp_Cli_Command_Twitter extends Garp_Cli_Command_Twitter_Abstract {

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
}

# End of file