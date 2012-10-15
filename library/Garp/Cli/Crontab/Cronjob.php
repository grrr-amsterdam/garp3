<?php
/**
 * Garp_Cli_Crontab_Cronjob
 * Represents a single Cron job.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Crontab
 * @lastmodified $Date: $
 */
class Garp_Cli_Crontab_Cronjob {
	/**
 	 * Minute
 	 * @var String
 	 */
	protected $_minute = '*';


	/**
 	 * Hour
 	 * @var String
 	 */
	protected $_hour = '*';


	/**
 	 * Day of month
 	 * @var String
 	 */
	protected $_dayOfMonth = '*';


	/**
 	 * Month
 	 * @var String
 	 */
	protected $_month = '*';


	/**
 	 * Day of week
 	 * @var String
 	 */
	protected $_dayOfWeek = '*';


	/**
 	 * User that the command will run as
 	 * @var String
 	 */
	protected $_user = false;


	/**
 	 * Command to execute
 	 * @var String
 	 */
	protected $_command;


	/**
 	 * Class constructor
 	 * @param Array $options
 	 * @return Void
 	 */
	public function __construct(array $options = array()) {
		if (empty($options['command'])) {
			throw new Garp_Cli_Crontab_Exception('command is a required option.');
		}
		foreach (array('minute', 'hour', 'dayOfMonth', 'month', 'dayOfWeek', 'user', 'command') as $key) {
			if (!empty($options[$key])) {
				$prop = '_'.$key;
				$this->{$prop} = $options[$key];
			}
		}
	}


	/**
 	 * Parse an existing cron job rule from the crontab file.
 	 * @param String $cronjob
 	 * @return Garp_Cli_Crontab_Cronjob
 	 */
	public static function fromString($cronjob) {
		// implement me...
	}


	/**
 	 * Create a cronjob rule for use in the crontab file.
 	 * @return String
 	 */
	public function __toString() {
		// implement me...
		return '';
	}
}
