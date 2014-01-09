<?php

interface Garp_Cli_Command_Twitter_Interface {

	public function update();
	public function fetchConfig();
	public function fetchTweets($config);
}

# End of file