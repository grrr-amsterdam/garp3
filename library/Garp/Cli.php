<?php
/**
 * Garp_Cli
 * Command line interface
 * @author Harmen Janssen, David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @lastmodified $Date: $
 */
class Garp_Cli {
	/**@#+
 	 * String coloring constants
 	 * @var String
 	 */
	const RED = '2;31';
	const GREEN = '2;32';
	const BLUE = '2;34';
    /**#@-*/


	/**
	 * Print line.
	 * @param String $s The string.
	 * @param String $color Show string in color?
	 * @param Boolean $appendNewline Wether to add a newline character
	 * @return Void
	 */
	public static function lineOut($s, $color = null, $appendNewline = true) {
		if ($color) {
			self::addStringColoring($s, $color);
		}
		echo "{$s}".($appendNewline ? "\n" : '');
	}


	/**
	 * Print line in red.
	 * @param String $s
	 * @return Void
	 */
	public static function errorOut($s) {
		self::addStringColoring($s, self::RED);
		self::lineOut($s);
	}
	

	/**
 	 * Print line in a certain color
 	 * Inspired by Garp_Model_Spawn_Util::addStringColoring()
 	 * @param String $s
 	 * @param String $color
 	 * @return Void
 	 */
	public static function addStringColoring(&$s, $color) {
		$prevEnc = mb_internal_encoding();
		mb_internal_encoding("UTF-8");
		$s = "\033[{$color}m{$s}\033[0m\n";
		mb_internal_encoding($prevEnc);
	}
	
	/**
	 * Receive input from the commandline.
	 * @param String $prompt Something to say to the user indicating your waiting for a response
	 * @param Boolean $trim Wether to trim the response
	 * @return String The user's response
	 */
	public static function prompt($prompt = '', $trim = true) {
		$prompt && self::lineOut($prompt);
		self::lineOut('> ', null, false);
		$response = fgets(STDIN);
		
		if ($trim) {
			$response = trim($response);
		}
		return $response;
	}
	
	
	/**
	 * Force user to confirm a question with yes or no.
	 * @param String $msg Question or message to display. A prompt (>) will be added.
	 * @return Boolean Returns true if answer was 'y' or 'Y', no enter needed.
	 */
	public static function confirm($msg) {
		print $msg.' > ';
		system('stty -icanon');
		$handle = fopen ('php://stdin', 'r');
		$char = fgetc($handle);
		system('stty icanon');
		print "\n";
		return $char === 'y' || $char === 'Y';
	}
	
	
	/**
	 * PARSE ARGUMENTS
	 * 
	 * This command line option parser supports any combination of three types
	 * of options (switches, flags and arguments) and returns a simple array.
	 * 
	 * [pfisher ~]$ php test.php --foo --bar=baz
	 *   ["foo"]   => true
	 *   ["bar"]   => "baz"
	 * 
	 * [pfisher ~]$ php test.php -abc
	 *   ["a"]     => true
	 *   ["b"]     => true
	 *   ["c"]     => true
	 * 
	 * [pfisher ~]$ php test.php arg1 arg2 arg3
	 *   [0]       => "arg1"
	 *   [1]       => "arg2"
	 *   [2]       => "arg3"
	 * 
	 * [pfisher ~]$ php test.php plain-arg --foo --bar=baz --funny="spam=eggs" --also-funny=spam=eggs \
	 * > 'plain arg 2' -abc -k=value "plain arg 3" --s="original" --s='overwrite' --s
	 *   [0]       => "plain-arg"
	 *   ["foo"]   => true
	 *   ["bar"]   => "baz"
	 *   ["funny"] => "spam=eggs"
	 *   ["also-funny"]=> "spam=eggs"
	 *   [1]       => "plain arg 2"
	 *   ["a"]     => true
	 *   ["b"]     => true
	 *   ["c"]     => true
	 *   ["k"]     => "value"
	 *   [2]       => "plain arg 3"
	 *   ["s"]     => "overwrite"
	 *
	 * @author              Patrick Fisher <patrick@pwfisher.com>
	 * @since               August 21, 2009
	 * @see                 http://www.php.net/manual/en/features.commandline.php
	 *                      #81042 function arguments($argv) by technorati at gmail dot com, 12-Feb-2008
	 *                      #78651 function getArgs($args) by B Crawford, 22-Oct-2007
	 * @usage               $args = CommandLine::parseArgs($_SERVER['argv']);
	 */
	public static function parseArgs($argv) {
		array_shift($argv);
		$out = array();
		foreach ($argv as $arg) {
			// --foo --bar=baz
			if (substr($arg, 0, 2) == '--') {
				$eqPos = strpos($arg,'=');

				// --foo
				if ($eqPos === false){
					$key		= substr($arg,2);
					$value		= isset($out[$key]) ? $out[$key] : true;
					$out[$key]	= $value;
				}
				// --bar=baz
				else {
					$key		= substr($arg,2,$eqPos-2);
					$value		= substr($arg,$eqPos+1);
					$out[$key]	= $value;
				}
			}
			// -k=value -abc
			elseif (substr($arg, 0, 1) == '-') {
				
                // -k=value
				if (substr($arg, 2, 1) == '=') {
					$key		= substr($arg,1,1);
                    $value		= substr($arg,3);
                    $out[$key]	= $value;
				}
				// -abc
				else {
					$chars = str_split(substr($arg, 1));
					foreach ($chars as $char) {
						$key		= $char;
						$value		= isset($out[$key]) ? $out[$key] : true;
						$out[$key]	= $value;
					}
				}
			}
            // plain-arg
			else {
				$value	= $arg;
				$out[]	= $value;
			}
		}
		return $out;
	}


	/**
 	 * For some functionality you absolutely need an HTTP context.
 	 * This method mimics a standard Zend request.
 	 * @param String $uri
 	 * @return String The response body
 	 */
	public static function makeHttpCall($uri) {
		$request = new Zend_Controller_Request_Http();
		$request->setRequestUri($uri);
		$application = Zend_Registry::get('application');
		$front = $application->getBootstrap()->getResource('FrontController');
		$default = $front->getDefaultModule();
		if (null === $front->getControllerDirectory($default)) {
			throw new Zend_Application_Bootstrap_Exception(
				'No default controller directory registered with front controller'
			);
		}
		$front->setParam('bootstrap', $application->getBootstrap());
		// Make sure we aren't blocked from the ContentController as per the rules in acl.ini
		$front->unregisterPlugin('Garp_Controller_Plugin_Auth');
		// Make sure no output is rendered
		$front->returnResponse(true);

		$response = $front->dispatch($request);
		return $response;
	}


	/**
 	 * On shell, you must return 0 on success, 1 on failure.
 	 * This method deals with that. Feed it any expression and 
 	 * it will exit the right way
 	 * @param Mixed $bool
 	 * @return Void
 	 */
	public static function halt($bool) {
		// Convert to boolean
		$bool = !!$bool;
		// Toggle it: PHP uses 0 for FALSE, shell uses 0 for TRUE
		$bool = !$bool;
		exit((int)$bool);
	}
}
