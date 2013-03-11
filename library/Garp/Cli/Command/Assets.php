<?php
/**
 * Garp_Cli_Command_Assets
 * class description
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Garp_Cli_Command
 */
class Garp_Cli_Command_Assets extends Garp_Cli_Command {
	/**
 	 * Minify assets
 	 * @param Array $args
 	 * @return Boolean
 	 */
	public function minifyJs(array $args = array()) {
		$ini = Zend_Registry::get('config');
		if (empty($ini->assets->js)) {
			Garp_Cli::errorOut("You do not have a valid asset configuration specified in application.ini.\n".
				"Configure like this:\n".
				'assets.js.main_js.filename = "main.min.js"'."\n".
				'assets.js.main_js.sourcefiles[] = "libs/myplugin.js"'."\n".
				'assets.js.main_js.sourcefiles[] = "main.js"'."\n".
				"This will combine libs/myplugin.js and main.js into main.min.js."
			);
			return false;
		}
		
		$jsRoot = ltrim($ini->assets->js->basePath ?: 'js', '/');
		$assets = $ini->assets->js->toArray();
		unset($assets['basePath']);
		foreach ($assets as $key => $assetSettings) {
			if (empty($assetSettings['sourcefiles']) ||
				empty($assetSettings['filename'])) {
				Garp_Cli::errorOut($key.' is not configured correctly. "sourcefiles" and "filename" are required keys.');
				return false;
			}
			$minifier = new Garp_Assets_Minifier(APPLICATION_PATH.'/../public/'.$jsRoot);
			$minifier->minifyJs($assetSettings['sourcefiles'], $assetSettings['filename']);
		}
		return true;
	}
}
