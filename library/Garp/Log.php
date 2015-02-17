<?php
class Garp_Log extends Zend_Log {
	/**
 	* Shortcut to fetching a configured logger instance
 	*/
	static public function factory($config = array()) {
		if (is_string($config)) {
			// Assume $config is a filename
			$filename = $config;
			$config = array(
				'timestampFormat' => 'Y-m-d',
				array(
					'writerName' => 'Stream',
					'writerParams' => array(
						'stream' => self::_getLoggingDirectory() . DIRECTORY_SEPARATOR . $filename
					)
				)
			);
		}
		return parent::factory($config);
	}

	static protected function _getLoggingDirectory() {
		$target = APPLICATION_PATH.'/data/logs/';
		if (Zend_Registry::isRegistered('config') &&
			!empty(Zend_Registry::get('config')->logging->directory)) {
			$target = Zend_Registry::get('config')->logging->directory;
		}
		if (!is_dir($target)) {
			@mkdir($target);
		}
		return $target;
	}
}
