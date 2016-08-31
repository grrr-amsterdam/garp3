<?php
/**
 * Garp_Cli_Crontab_Cronjob
 * Represents a single Cron job.
 *
 * @package Garp_Cli
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Cli_Crontab_Cronjob {
    /**
     * Minute
     *
     * @var string
     */
    protected $_minute = '*';

    /**
     * Hour
     *
     * @var string
     */
    protected $_hour = '*';

    /**
     * Day of month
     *
     * @var string
     */
    protected $_dayOfMonth = '*';

    /**
     * Month
     *
     * @var string
     */
    protected $_month = '*';

    /**
     * Day of week
     *
     * @var string
     */
    protected $_dayOfWeek = '*';

    /**
     * User that the command will run as
     *
     * @var string
     */
    protected $_user = false;

    /**
     * Command to execute
     *
     * @var string
     */
    protected $_command;

    /**
     * Class constructor
     *
     * @param array $options
     * @return void
     */
    public function __construct(array $options = array()) {
        if (empty($options['command'])) {
            throw new Garp_Cli_Crontab_Exception('command is a required option.');
        }
        $validOptions = array(
            'minute', 'hour', 'dayOfMonth', 'month', 'dayOfWeek', 'user', 'command'
        );
        foreach ($validOptions as $key) {
            if (!empty($options[$key])) {
                $prop = '_' . $key;
                $this->{$prop} = $options[$key];
            }
        }
    }

    /**
     * Parse an existing cron job rule from the crontab file.
     *
     * @param string $cronjob
     * @return Garp_Cli_Crontab_Cronjob
     */
    public static function fromString($cronjob) {
        // implement me...
    }

    /**
     * Create a cronjob rule for use in the crontab file.
     *
     * @return string
     */
    public function __toString() {
        // implement me...
        return '';
    }
}
