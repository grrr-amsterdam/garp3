<?php
/**
 * Garp_Service_Slack
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Service
 * @lastmodified $Date: $
 */
class Garp_Service_Slack {
	const API_URL = 'https://hooks.slack.com/services/%s';
	const ERROR_TOKEN_UNAVAILABLE = 
		"The webhook token for Slack was not configured properly.";
	const CONFIG_INSTRUCTION = 
		"Please set slack.webhook.token to the string that is used 
		in the Slack webhook integration. It consists of three 
		strings, separated by slashes, i.e.: 
		F0E33IA/B921F21/raiu3221hjkD21g.
		You can find it on your Slack integrations page.";


	/**
 	 * Post a message in a Slack channel.
 	 * @param $text		Text to post in the Slack message
 	 * @param $params	Extra, optional Slack parameters that
 	 *					override the Incoming Webhook settings.
 	 *					f.i. 'username', 'icon_emoji', 'channel'
 	 */
	public function postMessage($text, $params = array()) {
		$params['text'] = $text;

		return $this->_fsock_post(
			$this->_constructWebhookUrl(),
			$this->_constructParameters($params)
		);
	}

	/**
 	 * Tests if Slack is enabled for this project.
 	 * @return Boolean
 	 */
	public function isEnabled() {
		try {
			$token = $this->_fetchToken();
		} catch (Exception $e) {
			return false;
		}

		return true;
	}

	/**
 	 * Returns a string that describes how to
 	 * enable Slack for this project.
 	 */
	public function getConfigInstruction() {
		return str_replace("\t", '', self::CONFIG_INSTRUCTION);
	}

	protected function _constructParameters(array $params) {
		return array(
			'payload' => json_encode($params)
		);
	}

	protected function _constructWebhookUrl() {
		$token = $this->_fetchToken();
		$url = sprintf(self::API_URL, $token);

		return $url;
	}

	protected function _fetchToken() {
		$ini = Zend_Registry::get('config');
		if (
			!isset($ini->slack) ||
			!isset($ini->slack->webhook) ||
			!isset($ini->slack->webhook->token) ||
			empty($ini->slack->webhook->token)
		) {
			$error = self::ERROR_TOKEN_UNAVAILABLE
				. "\n" . self::CONFIG_INSTRUCTION;
			throw new Exception($error);
		}
		$token = $ini->slack->webhook->token;

		return $token;
	}

	protected function _fsock_post($url, $data) {
		$parsedUrl = parse_url($url);
		$isHttps = $parsedUrl['scheme'] === 'https';
		$host = $parsedUrl['host'];
		$port = $isHttps ? 443 : 80;

		$fp = fsockopen(($isHttps ? 'ssl://' : '') . $host, $port);
		$content = http_build_query($data);

		fwrite($fp, "POST {$parsedUrl['path']} HTTP/1.1\r\n");
		fwrite($fp, "Host: {$host}\r\n");
		fwrite($fp, "Content-Type: application/x-www-form-urlencoded\r\n");
		fwrite($fp, "Content-Length: " . strlen($content) . "\r\n");
		fwrite($fp, "Connection: close\r\n");
		fwrite($fp, "\r\n");
		fwrite($fp, $content);

		header('Content-type: text/plain');
		$output = '';
		while (!feof($fp)) {
    		$output .= fgets($fp, 1024);
		}

		fclose($fp);
		return $output;
	}
}
