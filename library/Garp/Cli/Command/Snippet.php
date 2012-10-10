<?php
/**
 * Garp_Cli_Command_Snippet
 * Create snippets from the commandline
 *
 * @author       $Author: harmen $
 * @modifiedby   $LastChangedBy: harmen $
 * @version      $LastChangedRevision: 6326 $
 * @package      Garp
 * @subpackage   Cli
 * @lastmodified $LastChangedDate: 2012-09-18 10:08:29 +0200 (Tue, 18 Sep 2012) $
 */
class Garp_Cli_Command_Snippet extends Garp_Cli_Command {
	/**
 	 * Create new snippet
 	 */
	public function create() {
		Garp_Cli::lineOut('Please provide the following values');
		$data = array(
			'identifier' => Garp_Cli::prompt('Identifier'),
			'uri' => Garp_Cli::prompt('Url'),
		);
		$checks = array(
			array('has_name', 'Does this snippet have a name?', 'y'),
			array('has_html', 'Does this snippet contain HTML?', 'y'),
			array('has_text', 'Does this snippet contain text?', 'n'),
			array('has_image', 'Does this snippet have an image?', 'n'),
		);
		foreach ($checks as $check) {
			$key = $check[0];
			$question = $check[1];
			$default = $check[2];
			if ($default == 'y') {
				$question .= ' Yn';
			} elseif ($default == 'n') {
				$question .= ' yN';
			}
			$userResponse = trim(Garp_Cli::prompt($question));
			if (!$userResponse) {
				$userResponse = $default;
			}
			if (strtolower($userResponse) == 'y' || $userResponse == 1) {
				$data[$key] = 1;
			} elseif (strtolower($userResponse) == 'n' || $userResponse == 0) {
				$data[$key] = 0;
			}
		}

		$classLoader = Garp_Util_Loader::getInstance();
		if (!$classLoader->isLoadable('Model_Snippet')) {
			Garp_Cli::errorOut('The Snippet model could not be autoloaded. Spawn it first, dumbass!');
			return;
		}
		$snippetModel = new Model_Snippet();
		try {
			$snippetID = $snippetModel->insert($data);
			$snippet = $snippetModel->fetchRow($snippetModel->select()->where('id = ?', $snippetID));
			Garp_Cli::lineOut('New snippet successfully inserted. Id: #'.$snippetID.', Identifier: "'.$snippet->identifier.'"');
		} catch (Garp_Model_Validator_Exception $e) {
			Garp_Cli::errorOut($e->getMessage());
		}
	}


	/**
 	 * Help
 	 */
	public function help() {
		Garp_Cli::lineOut('Usage:');
 	    Garp_Cli::lineOut('  g Snippet create');
	}
}
