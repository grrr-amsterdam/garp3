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

	/**
 	 * @var Garp_Service_Slack_Config $_config
 	 */
	protected $_config;


	public function __construct(Garp_Service_Slack_Config $config = null) {
		if (!$config) {
			$config = new Garp_Service_Slack_Config();
		}

		$this->_config = $config;
	}

	/**
 	 *  @return Garp_Service_Slack_Config
 	 */
	public function getConfig() {
		return $this->_config;
	}

	/**
 	 * Post a message in a Slack channel.
 	 * @param $text		Text to post in the Slack message
 	 * @param $params	Extra, optional Slack parameters that
 	 *					override the app-wide settings in app.ini,
 	 *					that in turn override Slack's Incoming 
 	 *					Webhook settings.
 	 *					f.i.:
 	 *					'username' => 'me',
 	 *					'icon_emoji' => ':ghost:',
 	 *					'channel' => '#my-channel'
 	 */
	public function postMessage($text, $params = array()) {
		$config = $this->getConfig();
		$params['text'] = $text;
		$params = $config->getParams($params);

		return $this->_fsock_post(
			$this->_constructWebhookUrl(),
			$this->_constructParameters($params)
		);
	}

	/**
 	 * Wraps code in pre-formatting markup tags.
 	 */
	public function wrapCodeMarkup($string) {
		return "```\n" . $string . "\n```";
	}

	protected function _constructParameters(array $params) {
		return array(
			'payload' => json_encode($params)
		);
	}

	protected function _constructWebhookUrl() {
		$token = $this->getConfig()->getToken();
		$url = sprintf(self::API_URL, $token);

		return $url;
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

		$output = '';
		while (!feof($fp)) {
    		$output .= fgets($fp, 1024);
		}

		fclose($fp);
		return $output;
	}
}
