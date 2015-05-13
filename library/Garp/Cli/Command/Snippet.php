<?php
/**
 * Garp_Cli_Command_Snippet
 * Create snippets from the commandline
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.3
 * @package      Garp_Cli_Command
 */
class Garp_Cli_Command_Snippet extends Garp_Cli_Command {
	const DEFAULT_SNIPPET_CONFIG_FILE = 'snippets.ini';

	/** Wether to overwrite snippets */
	protected $_overwrite = false;

	protected $_allowedArguments = array(
		'create' => array('i', 'interactive', 'file', 'overwrite')
	);

	/**
 	 * Wether the Snippet model can be autoloaded
 	 */
	protected $_loadable;

	/**
 	 * Create new snippet
 	 */
	public function create(array $args = array()) {
		$this->_overwrite = isset($args['overwrite']) && $args['overwrite'];
		if (isset($args['i']) || isset($args['interactive'])) {
			return $this->_createInteractive();
		}
		$file = $this->_parseFileFromArguments($args);
		$file = APPLICATION_PATH . '/configs/' . $file;
		$this->_createFromFile($file);
	}

	/**
 	 * Import i18n string files (ex. data/i18n/nl.php) as snippets
 	 */
	public function storeI18nStrings(array $args = array()) {
		// @todo Adapt for multiple languages
		$nl = $this->_loadI18nStrings('nl');
		$en = $this->_loadI18nStrings('en');

		// Wether the snippets are editable in the CMS
		$editable = !array_key_exists('editable', $args) || !!$args['editable'];
		$this->_overwrite = isset($args['overwrite']) && $args['overwrite'];

		foreach ($nl as $key => $value) {
			$snippet = array(
				'has_text' => 1,
				'identifier' => $key,
				'is_editable' => $editable,
				'text' => array(
					'nl' => $value
				)
			);
			if (array_key_exists($key, $en)) {
				$snippet['text']['en'] = $en[$key];
			}
			$existing = $this->_fetchExisting($key);
			if (!$this->_overwrite && $existing) {
				Garp_Cli::lineOut('Skipping ' . $key . '. Snippet already exists.');
				continue;
			}
			$this->_insertOrUpdate($snippet, $existing);
		}
	}

	/**
 	 * Create a bunch of snippets from a file
 	 */
	protected function _createFromFile($file) {
		if (!file_exists($file)) {
			Garp_Cli::errorOut("File not found: {$file}. Adding no snippets.");
			return false;
		}
		$config = new Garp_Config_Ini($file, APPLICATION_ENV);
		if (!isset($config->snippets)) {
			Garp_Cli::lineOut('Could not find any snippets. I\'m stopping now... This is awkward.');
			return true;
		}

		$snippetNoun = 'snippets';
		$snippetCount = count($config->snippets);
		if ($snippetCount === 1) {
			$snippetNoun = 'snippet';
		}
		Garp_Cli::lineOut("About to add {$snippetCount} {$snippetNoun} from {$file}");
		Garp_Cli::lineOut('');
		$this->_createSnippets($config->snippets);
		Garp_Cli::lineOut('Done.');
		Garp_Cli::lineOut('');
	}

	protected function _createSnippets($snippets) {
		foreach ($snippets as $identifier => $data) {
			$identifier = $this->_normalizeIdentifier($identifier);
			$snippetData = $data->toArray();
			$snippetData['identifier'] = isset($snippetData['identifier']) ?
				$snippetData['identifier'] : $identifier;
			$existing = $this->_fetchExisting($identifier);
			if (!$this->_overwrite && $existing) {
				Garp_Cli::lineOut('Skipping "' . $identifier . '". Snippet already exists.');
				continue;
			}
			$this->_insertOrUpdate($snippetData, $existing);
		}
	}

	protected function _insertOrUpdate(array $snippetData, $existing) {
		if ($existing) {
			$snippet = $this->_update($snippetData, $existing->id);
			Garp_Cli::lineOut('Overwriting existing snippet "' . $snippet->identifier . '"');
			return;
		}
		$snippet = $this->_create($snippetData);
		Garp_Cli::lineOut('New snippet inserted. Id: #'.$snippet->id.', Identifier: "'.$snippet->identifier.'"');
	}

	/**
 	 * Create snippet interactively
 	 */
	protected function _createInteractive() {
		Garp_Cli::lineOut('Please provide the following values');
		$data = array(
			'identifier' => Garp_Cli::prompt('Identifier')
		);
		if ($snippet = $this->_fetchExisting($data['identifier'])) {
			Garp_Cli::lineOut('Snippet already exists. Id: #'.$snippet->id, Garp_Cli::GREEN);
			return true;
		}

		$data['uri'] = Garp_Cli::prompt('Url');
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

		$snippet = $this->_create($data);
		Garp_Cli::lineOut('New snippet inserted. Id: #'.$snippet->id.', Identifier: "'.$snippet->identifier.'"');
	}

	/**
 	 * Insert new snippet
 	 * @param Array $data Snippet data
 	 */
	protected function _create(array $data) {
		$this->_validateLoadable();
		$snippetModel = new Model_Snippet();
		try {
			$snippetID = $snippetModel->insert($data);
			$snippet = $snippetModel->fetchRow($snippetModel->select()->where('id = ?', $snippetID));
			return $snippet;
		} catch (Garp_Model_Validator_Exception $e) {
			Garp_Cli::errorOut($e->getMessage());
		}
	}

	protected function _update($snippetData, $snippetID) {
		$this->_validateLoadable();
		$snippetModel = new Model_Snippet();
		try {
			$snippetModel->update($snippetData, "id = {$snippetID}");
			$snippet = $snippetModel->fetchRow($snippetModel->select()->where('id = ?', $snippetID));
			return $snippet;
		} catch (Garp_Model_Validator_Exception $e) {
			Garp_Cli::errorOut($e->getMessage());
		}
	}

	/**
 	 * Fetch existing snippet by identifier
 	 */
	protected function _fetchExisting($identifier) {
		$this->_validateLoadable();
		$snippetModel = new Model_Snippet();
		$select = $snippetModel->select()
			->where('identifier = ?', $identifier)
		;
		$row = $snippetModel->fetchRow($select);
		return $row;
	}

	/**
 	 * Make sure the snippet model is loadable
 	 */
	protected function _validateLoadable() {
		if ($this->_loadable) {
			return true;
		}
		$classLoader = Garp_Loader::getInstance();
		if (!$classLoader->isLoadable('Model_Snippet')) {
			throw new Exception('The Snippet model could not be autoloaded. Spawn it first, dumbass!');
		}
		$this->_loadable = true;
	}

	/**
 	 * Load i18n strings
 	 * @param String $locale
 	 * @return Array
 	 */
	protected function _loadI18nStrings($locale) {
		$file = APPLICATION_PATH . '/data/i18n/' . $locale . '.php';
		if (!file_exists($file)) {
			throw new Exception('File not found: '.$file);
		}
		ob_start();
		$data = include($file);
		ob_end_clean();
		return $data;
	}

	public function help() {
		Garp_Cli::lineOut('Usage:');
		Garp_Cli::lineOut('To create snippets from /application/configs/snippets.ini:');
		Garp_Cli::lineOut('  g snippet create', Garp_Cli::BLUE);
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('Or to create snippets from a custom file:');
		Garp_Cli::lineOut('  g snippet create --file=myfile.ini', Garp_Cli::BLUE);
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('Overwrite existing snippets:');
		Garp_Cli::lineOut('  g snippet create --overwrite');
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('To create a snippet interactively:');
 	    Garp_Cli::lineOut('  g snippet create -i', Garp_Cli::BLUE);
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('To add system snippets for an internationalized website:');
		Garp_Cli::lineOut('  g snippet storeI18nStrings', Garp_Cli::BLUE);
		Garp_Cli::lineOut('');
	}

	protected function _parseFileFromArguments(array $args = array()) {
		if (array_key_exists('file', $args)) {
			return $args['file'];
		}
		return self::DEFAULT_SNIPPET_CONFIG_FILE;
	}

	protected function _normalizeIdentifier($identifier) {
		return str_replace('_', ' ', $identifier);
	}
}
