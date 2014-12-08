<?php
class Garp_Service_Slack {
	const API_HOST = 'hooks.slack.com';


	/**
 	 * @param $text		Text to post in the Slack message
 	 * @param $params	Extra, optional Slack parameters that
 	 *					override the Incoming Webhook settings.
 	 *					f.i. 'username', 'icon_emoji', 'channel'
 	 */
	public postMessage($text, $params = array()) {
		$params['text'] = $text;

		$ini = Zend_Registry::get('config');
		$token = $ini->slack->webhook->token;
		exit($token);

	"https://hooks.slack.com/services/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",
	array(
		'payload' => json_encode($payload)
	)

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
