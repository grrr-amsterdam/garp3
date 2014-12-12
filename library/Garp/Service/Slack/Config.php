<?php
/**
 * Garp_Service_Slack_Config
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Slack
 * @lastmodified $Date: $
 */
class Garp_Service_Slack_Config {
	const CONFIG_INSTRUCTION = 
		"Please set slack.webhook.token to the string that is used 
		in the Slack webhook integration. It consists of three 
		strings, separated by slashes, i.e.: 
		F0E33IA/B921F21/raiu3221hjkD21g.
		You can find it on your Slack integrations page.";
	const ERROR_TOKEN_UNAVAILABLE = 
		"The webhook token for Slack was not configured properly.";

	protected $_token;
	protected $_channel;
	protected $_icon_emoji;
	protected $_username;


	/**
 	 *	@param Array $config	A config array containing at
 	 *							least 'token', and optionally
 	 *							'channel', 'icon_emoji' and
 	 *							'username'.
 	 *							If not provided, the values from
 	 *							app.ini will be used.
 	 */
	public function __construct(array $config = null) {
		if (!$config) {
			$config = $this->_loadAppWideConfig();
		}

		$this->_validateConfig($config);

		foreach ($config as $param => $value) {
			$this->{'_' . $param} = $value;
		}
	}

	/**
 	 *	Returns the app-wide configuration parameters.
 	 *	@param Array $overrides		Optional values to pragmatically 
 	 *								override the app-wide configuration.
 	 */
	public function getParams(array $overrides = null) {
		$params = array(
			'token' => $this->_token,
			'channel' => $this->_channel,
			'icon_emoji' => $this->_icon_emoji,
			'username' => $this->_username
		);

		if ($overrides) {
			$params = array_merge($params, $overrides);
		}

		return $params;
	}

	public function getToken() {
		return $this->_token;
	}	

	public function getChannel() {
		return $this->_channel;
	}

	public function getIconEmoji() {
		return $this->_icon_emoji;
	}

	public function getUsername() {
		return $this->_username;
	}

	/**
 	 * Returns a string that describes how to
 	 * enable Slack for this project.
 	 */
	public function getConfigInstruction() {
		return str_replace("\t", '', self::CONFIG_INSTRUCTION);
	}

	protected function _loadAppWideConfig() {
		$ini = Zend_Registry::get('config');
		$params = $ini->slack;

		return $params->toArray();
	}

	protected function _validateConfig(array $config) {
		if (
			!array_key_exists('token', $config) ||
			empty($config['token'])
		) {
			throw new Exception(
				self::ERROR_TOKEN_UNAVAILABLE . "\n"
				. $this->getConfigInstruction()
			);
		}
	}
}
