<?php
/**
 * Garp_Cli_Command_Ses
 * Perform administrative SES functions.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Garp
 * @subpackage   Cli
 */
class Garp_Cli_Command_Ses extends Garp_Cli_Command {
	public function verify(array $args = array()) {
		if (empty($args)) {
			Garp_Cli::errorOut('No email address given. See g Ses help for help.');
			return false;
		}

		$email = $args[0];
		$ses = new Garp_Service_Amazon_Ses();
		$ses->verifyEmailAddress($email);
		Garp_Cli::lineOut('Done. A confirmation email has been sent to '.$email);
		return true;
	}

	public function delete(array $args = array()) {
		if (empty($args)) {
			Garp_Cli::errorOut('No email address given. See g Ses help for help.');
			return false;
		}

		$email = $args[0];
		$ses = new Garp_Service_Amazon_Ses();
		$ses->deleteVerifiedEmailAddress($email);
		Garp_Cli::lineOut('Done.');
		return true;
	}

	public function listAddresses(array $args = array()) {
		$ses = new Garp_Service_Amazon_Ses();
		$list = $ses->listVerifiedEmailAddresses();
		if (empty($list)) {
			Garp_Cli::lineOut('No verified email addresses found.');
		} else {
			foreach ($list as $addr) {
				Garp_Cli::lineOut(' - '.$addr);
			}
		}
		return true;
	}

	public function quota(array $args = array()) {
		$ses = new Garp_Service_Amazon_Ses();
		$quota = $ses->getSendQuota();
		foreach ($quota as $key => $value) {
			Garp_Cli::lineOut("$key: $value");
		}
		return true;
	}

	public function stats(array $args = array()) {
		$ses = new Garp_Service_Amazon_Ses();
		$stats = $ses->getSendStatistics();
		foreach ($stats as $key => $value) {
			Garp_Cli::lineOut("$key: $value");
		}
		return true;
	}

	public function help() {
		Garp_Cli::lineOut('Usage:');
		Garp_Cli::lineOut('Verify email address:');
		Garp_Cli::lineOut('  g Ses verify <email address>');
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('Delete email address:');
		Garp_Cli::lineOut('  g Ses delete <email address>');
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('List verified email addresses:');
		Garp_Cli::lineOut('  g Ses listAddresses');
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('See send quota:');
		Garp_Cli::lineOut('  g Ses quota');
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('See send statistics:');
		Garp_Cli::lineOut('  g Ses stats');
		Garp_Cli::lineOut('');
		return true;
	}
}
