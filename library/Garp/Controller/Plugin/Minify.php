<?php
class Garp_Controller_Plugin_Minify extends Zend_Controller_Plugin_Abstract {

	public function dispatchLoopShutdown() {
		$response = $this->getResponse();
		if ($response->isException() ||
			$response->isRedirect() ||
			!$response->getBody()) {
			return;
		}

		require_once APPLICATION_PATH . '/../library/Garp/3rdParty/minify/lib/Minify/HTML.php';
		$response->setBody(
			Minify_HTML::minify($response->getBody())
		);
	}

}
