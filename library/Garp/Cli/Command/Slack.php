<?php
/**
 * Garp_Cli_Command_Slack
 *
 * @package Garp_Cli_Command
 * @author  David Spreekmeester <david@grrr.nl>
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Cli_Command_Slack extends Garp_Cli_Command {
    const ERROR_EMPTY_SEND
        = "You didn't tell me *what* you'd like to send.";

    /**
     * Post a message in a Slack channel
     *
     * @param array $args
     * @return bool
     */
    public function send(array $args = array()) {
        if (!$args || !array_key_exists(0, $args) || empty($args[0])) {
            Garp_Cli::errorOut(self::ERROR_EMPTY_SEND);
            return false;
        }

        $slack = new Garp_Service_Slack();
        return $slack->postMessage($args[0]);
    }

    /**
     * Send a nicely formatted deploy notification.
     * Note: branch and user are given by Capistrano. The user corresponds to the ssh user used to
     * login to the server, not the Git user.
     *
     * @param array $args
     * @return bool
     */
    public function sendDeployNotification(array $args = array()) {
        $branch = array_get($args, 'branch', 'unknown');
        $user = array_get($args, 'user', 'unknown');
        $gitVersion = array_get($args, 'git-version', 'unknown');

        $config = Zend_Registry::get('config');
        $appName = $config->app->name;
        $version = new Garp_Semver();

        $env = APPLICATION_ENV;

        $slackParams = $config->slack->toArray();
        $slackParams['icon_emoji'] = ':rocket:';

        $slackConfig = new Garp_Service_Slack_Config($slackParams);
        $slack = new Garp_Service_Slack($slackConfig);

        if ($gitVersion && strpos($gitVersion, 'fatal') === false) {
            $version = $gitVersion;
        }

        return $slack->postMessage(
            '',
            array(
                'attachments' => array(
                    array(
                        'title_link' => (string)new Garp_Util_FullUrl('/'),
                        'title' => "{$appName} {$version} was deployed to the {$env} server",
                        'color' => '#7CD197',
                        'fields' => array(
                            array(
                                'title' => 'User',
                                'value' => ucfirst($user),
                                'short' => true
                            ),
                            array(
                                'title' => 'Branch',
                                'value' => $branch,
                                'short' => true
                            ),
                        )
                    )
                )
            )
        );
    }

    /**
     * Help
     *
     * @return bool
     */
    public function help() {
        Garp_Cli::lineOut('Usage:');
        Garp_Cli::lineOut('Send Slack message:');
        Garp_Cli::lineOut('  g slack send "Hello world"', Garp_Cli::BLUE);
        Garp_Cli::lineOut('');
        return true;
    }
}


