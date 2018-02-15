<?php
/**
 * Garp_Cli_Command_Email
 * Send email using the CLI
 *
 * @package Garp_Cli_Command
 * @author  Floris S. Koch <floris@grrr.nl>
 */
class Garp_Cli_Command_Email extends Garp_Cli_Command {

    const REQUIRED_FLAGS = array('to', 'subject', 'message');

    /**
     * Send email
     *
     * @param array $args
     * @return void
     */
    public function send(array $args) {
        if (!$this->_validateArgs($args)) {
            return;
        }

        $mailer = new Garp_Mailer;

        if (getenv('CLI_EMAIL_SENDER')) {
            $mailer->setFromAddress(getenv('CLI_EMAIL_SENDER'));
        }

        $email = array(
            'to' => $args['to'],
            'subject' => $args['subject'],
            'message' => $args['message'],
        );

        try {
            $mailer->send($email);
            Garp_Cli::lineOut('Email sent!');
        } catch(Exception $e) {
            Garp_Cli::errorOut($e->getMessage());
        }
    }

    /**
     * Validates given arguments
     *
     * @param array $args
     * @return boolean
     */
    protected function _validateArgs(array $args): bool {
        foreach (self::REQUIRED_FLAGS as $flag) {
            if (!array_key_exists($flag, $args)) {
                Garp_Cli::errorOut("No {$flag} flag provided.");
                return false;
            }
        }
        return true;
    }
}
